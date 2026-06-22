<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Users\Address\Api;

use BaksDev\Core\Type\UserAgent\UserAgentGenerator;
use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileResult;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateInterval;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\Cache\ItemInterface;

#[Autoconfigure(public: true)]
final class AddressToGeocodeRequest
{
    private string|false $address;

    private string $userAgent;

    public function __construct(
        #[Target('usersAddressLogger')] private readonly LoggerInterface $logger,
        private readonly UserProfileByIdInterface $UserProfileByIdRepository,
        #[Autowire(env: 'DADATA_KEY')] private readonly ?string $KEY = null,
        #[Autowire(env: 'PROJECT_PROFILE')] private readonly ?string $PROJECT_PROFILE = null,
    )
    {
        $UserAgentGenerator = new UserAgentGenerator();
        $this->userAgent = $UserAgentGenerator->genDesktop();
    }

    public function setAddress(false|string $address): self
    {
        $address = str_replace('~', '/', $address);

        // Разбиваем строку по запятым
        $parts = explode(',', $address);

        /* Удаляем подъезд|домофон|этаж из адреса */
        $filtered = array_filter(array_map('trim', $parts), static function($part) {
            // Пропускаем части, которые полностью состоят из "номер слово" или "слово номер"
            return !preg_match('/^\d+\s+(?:подъезд|домофон|этаж)$|^(?:подъезд|домофон|этаж)\s+\d+$/iu', $part);
        });

        $address = array_unique($filtered);
        $address = implode(', ', $address);

        $address = str_replace(
            ['дом', 'корпус'],
            ['д.', 'к.'],
            $address,
        );

        /** находит строение в адресе, например 4Ас3 и разделяет его на 4А с3 */
        $pattern = '/(\d+[А-Яа-я]?)([сС]\d+)/u';
        $address = preg_replace($pattern, '$1 $2', $address);

        $this->address = $address;

        return $this;
    }

    public function find(): GeocodeAddressDTO|false
    {
        if(empty($this->address))
        {
            throw new InvalidArgumentException('Invalid Argument Exception Address');
        }

        $cache = new FilesystemAdapter('users-address');
        $fileName = md5($this->address);

        //$cache->deleteItem('autocomplete.'.$fileName);
        $content = $cache->get('autocomplete.'.$fileName, function(ItemInterface $item) {

            $item->expiresAfter(DateInterval::createFromDateString('1 day'));

            if(empty($this->KEY))
            {
                return false;
            }

            try
            {
                /** Получаем геоданные */
                $request = $this->TokenHttpClient()
                    ->request(
                        'POST',
                        '/suggestions/api/4_1/rs/suggest/address',
                        ['json' => ['query' => $this->address]],
                    );

                $content = current($request->toArray(false));

            }
            catch(Exception $exception)
            {
                $this->logger->critical(
                    sprintf('users-address: Ошибка %s при получении геолокации', $exception->getMessage()),
                    [
                        self::class.':'.__LINE__,
                        $this->address,
                    ],
                );

                return false;
            }

            if($request->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('users-address: Ошибка %s при получении геолокации', $request->getStatusCode()),
                    [
                        self::class.':'.__LINE__,
                        $request->getContent(false),
                        $this->address,
                    ],
                );

                return false;
            }

            $item->expiresAfter(DateInterval::createFromDateString('30 day'));

            return $content;

        });

        /** Если результат геолокации найден - присваиваем свойства DTO */
        if(false === empty($content))
        {
            $content = current($content);

            if(empty($content['data']))
            {
                return false;
            }

            $content = $content['data'];


            if(empty($content['geo_lat']) || empty($content['geo_lon']))
            {
                return false;
            }

            $resAddress = null;

            $GeocodeAddressDTO = new GeocodeAddressDTO();

            $resAddress[] = $content['country'];

            if($content['region'])
            {
                if($content['region_type'] === 'г' || $content['region_type'] === 'респ')
                {
                    $resAddress[] = $content['region_type'].'.'.$content['region'];
                }
                else
                {
                    $resAddress[] = $content['region_with_type']; // область
                }
            }

            if($content['area'] !== $content['region'] && $content['area'] !== $content['city'] && $content['area_type'] !== $content['city_type'])
            {
                $resAddress[] = $content['area'] ? $content['area_type'].'.'.$content['area'] : null; // город
            }

            if($content['city'] !== $content['region'])
            {
                $resAddress[] = $content['city'] ? $content['city_type'].'.'.$content['city'] : null; // город
            }

            $resAddress[] = $content['settlement'] ? $content['settlement_type'].'.'.$content['settlement'] : null; // поселок, деревня, территория
            $resAddress[] = $content['city_district'] ? $content['city_district_type'].'.'.$content['city_district'] : null; // район
            $resAddress[] = $content['street_with_type'] ? $content['street_type'].(in_array($content['street_type'], ['ул', 'ш', 'пер', 'дор']) ? '.' : ' ').$content['street'] : null; // улица

            $resAddress[] = $content['house'] ? str_replace('двлд', 'д', $content['house_type']).'.'.$content['house'] : null; // дом
            $resAddress[] = $content['block'] ? $content['block_type'].'.'.$content['block'] : null; //  корпус
            $resAddress[] = $content['flat'] ? $content['flat_type'].'.'.$content['flat'] : null; // квартира

            $cleanArray = array_filter($resAddress);
            $GeocodeAddressDTO->setAddress(implode(', ', $cleanArray));
            $GeocodeAddressDTO->setLatitude($content['geo_lat']);
            $GeocodeAddressDTO->setLongitude($content['geo_lon']);

            $GeocodeAddressDTO
                ->setCountry($content['country'])
                ->setArea($content['region_with_type'])
                ->setLocality($content['city_with_type'])
                ->setStreet($content['street_with_type'])
                ->setHouse($content['house'])
                ->setPostal($content['postal_code']);

            return $GeocodeAddressDTO;
        }


        /**
         * Если адрес не удалось определить - пробуем бесплатный сервис
         */

        if(empty($content))
        {
            // Разбиваем по пробелам
            $words = explode(' ', $this->address);

            // пробуем перебирать все вхождения удаляя последнее слово
            while(count($words) > 0)
            {
                $current = implode(' ', $words);

                //$cache->deleteItem('openstreetmap.'.$fileName);
                $content = $cache->get('openstreetmap.'.$fileName, function(ItemInterface $item) use ($current) {

                    $item->expiresAfter(5);

                    /** Получаем геоданные */
                    $request = $this->freeHttpClient()
                        ->request(
                            'GET',
                            '/search',
                            ['query' => [
                                'q' => $current,
                                'accept-language' => 'ru',
                                'format' => 'json',
                            ]],
                        );

                    if($request->getStatusCode() !== 200)
                    {
                        $this->logger->critical(
                            sprintf('users-address: Ошибка %s при получении геолокации', $request->getStatusCode()),
                            [
                                self::class.':'.__LINE__,
                                $request->getContent(),
                                $current,
                            ],
                        );

                        return false;
                    }

                    $item->expiresAfter(DateInterval::createFromDateString('30 day'));

                    return current($request->toArray(false));

                });

                if(empty($content))
                {
                    // Удаляем последнее слово (array_pop) и собираем строку поиска
                    array_pop($words);
                    sleep(1);
                    continue;
                }

                // Разбиваем по запятой, убираем пробелы у каждого элемента
                $parts = array_map('trim', explode(',', $content['display_name']));

                // Переворачиваем массив
                $parts = array_reverse($parts);

                $resAddress = array_filter($parts, function($item) {
                    return !preg_match('/^\d{5,7}$/', $item);
                });

                $GeocodeAddressDTO = new GeocodeAddressDTO();
                $GeocodeAddressDTO->setAddress(implode(', ', $resAddress));
                $GeocodeAddressDTO->setLatitude($content['lat']);
                $GeocodeAddressDTO->setLongitude($content['lon']);

                return $GeocodeAddressDTO;
            }

        }

        return false;
    }

    private function TokenHttpClient(): RetryableHttpClient
    {
        return new RetryableHttpClient(
            HttpClient::create(['headers' =>
                [
                    'User-Agent' => $this->userAgent,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Token '.$this->KEY,
                ],
            ])
                ->withOptions([
                    'base_uri' => 'https://suggestions.dadata.ru/',
                    'verify_host' => false,
                ]),
        );
    }

    private function freeHttpClient(): RetryableHttpClient
    {
        return new RetryableHttpClient(
            HttpClient::create(['headers' =>
                [
                    'User-Agent' => $this->userAgent,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ])
                ->withOptions([
                    'base_uri' => 'https://nominatim.openstreetmap.org/',
                    'verify_host' => false,
                ]),
        );
    }

    public function getAddressByProjectLocation(): false|GeocodeAddressDTO
    {
        if(empty($this->PROJECT_PROFILE))
        {
            return false;
        }

        $UserProfileResult = $this->UserProfileByIdRepository
            ->profile(new UserProfileUid($this->PROJECT_PROFILE))
            ->find();

        if($UserProfileResult instanceof UserProfileResult)
        {
            $GeocodeAddressDTO = new GeocodeAddressDTO();

            $GeocodeAddressDTO
                ->setLatitude($UserProfileResult->getLatitude())
                ->setLongitude($UserProfileResult->getLongitude())
                ->setAddress($UserProfileResult->getLocation());

            return $GeocodeAddressDTO;
        }

        return false;
    }

}
