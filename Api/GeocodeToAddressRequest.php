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

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Core\Type\UserAgent\UserAgentGenerator;
use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileResult;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateInterval;
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
final class GeocodeToAddressRequest
{
    /**
     * GPS широта.
     */
    private GpsLatitude|false $latitude = false;

    /**
     * GPS долгота.
     */
    private GpsLongitude|false $longitude = false;

    private string $userAgent;

    public function __construct(
        #[Target('usersAddressLogger')] private readonly LoggerInterface $logger,
        private readonly UserProfileByIdInterface $UserProfileByIdRepository,
        #[Autowire(env: 'DADATA_KEY')] private readonly ?string $KEY = null,
        #[Autowire(env: 'DADATA_SECRET')] private readonly ?string $SECRET = null,
        #[Autowire(env: 'PROJECT_PROFILE')] private readonly ?string $PROJECT_PROFILE = null,
    )
    {
        $UserAgentGenerator = new UserAgentGenerator();
        $this->userAgent = $UserAgentGenerator->genDesktop();
    }

    public function setLatitude(GpsLatitude $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function setLongitude(GpsLongitude $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function find(): GeocodeAddressDTO|false
    {
        if(empty($this->latitude) || empty($this->longitude))
        {
            throw new InvalidArgumentException('Invalid Argument latitude or longitude');
        }

        $cache = new FilesystemAdapter('users-address');
        $fileName = md5($this->latitude.$this->longitude);

        //$cache->deleteItem('dadata.'.$fileName);
        $content = $cache->get('dadata.'.$fileName, function(ItemInterface $item) {

            $item->expiresAfter(DateInterval::createFromDateString('1 day'));

            if(empty($this->KEY) || empty($this->SECRET))
            {
                return false;
            }

            /** Получаем геоданные */
            $request = $this->TokenHttpClient()
                ->request(
                    'POST',
                    '/suggestions/api/4_1/rs/geolocate/address',
                    ['json' => [
                        "lat" => $this->latitude->getFloat(),
                        "lon" => $this->longitude->getFloat(),
                    ]],
                );


            if($request->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('users-address: Ошибка %s при получении геолокации', $request->getStatusCode()),
                    [
                        self::class.':'.__LINE__,
                        $request->getContent(),
                        $this->latitude->getFloat().','.$this->longitude->getFloat(),

                    ],
                );

                return false;
            }

            $content = $request->toArray(false);

            if(empty($content['suggestions']))
            {
                return false;
            }

            $result = current($content['suggestions']);

            if(empty($result))
            {
                return false;
            }

            $result = $result['data'];

            if(empty($result) || empty($result['geo_lat']) || empty($result['geo_lon']))
            {
                return false;
            }

            $item->expiresAfter(DateInterval::createFromDateString('30 day'));

            return $result;

        });

        /** Если результат геолокации найден - присваиваем свойства DTO */
        if(false === empty($content))
        {
            $resAddress = null;

            $GeocodeAddressDTO = new GeocodeAddressDTO();
            //$GeocodeAddressDTO->setAddress($content['result']);

            $resAddress[] = $content['country'];
            $resAddress[] = $content['region'] ? ($content['region_type'] === 'г' ? 'г.'.$content['region'] : $content['region_with_type']) : null; // область


            if($content['area'] !== $content['region'] && $content['area'] !== $content['city'] && $content['area_type'] !== $content['city_type'])
            {
                $resAddress[] = $content['area'] ? $content['area_type'].'.'.$content['area'] : null; // город
            }

            if($content['city'] !== $content['region'])
            {
                $resAddress[] = $content['city'] ? $content['city_type'].'.'.$content['city'] : null; // город
            }

            $resAddress[] = $content['settlement'] ? $content['settlement_type'].''.$content['settlement'] : null; // поселок
            $resAddress[] = $content['city_district'] ? $content['city_district_type'].'.'.$content['city_district'] : null; // район
            $resAddress[] = $content['street_with_type'] ? $content['street_type'].(in_array($content['street_type'], ['ул', 'ш', 'пер']) ? '.' : ' ').$content['street'] : null; // улица
            $resAddress[] = $content['house'] ? $content['house_type'].'.'.$content['house'] : null; // дом
            $resAddress[] = $content['flat'] ? $content['flat_type'].'.'.$content['flat'] : null; // дом


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
            //$cache->deleteItem('openstreetmap.'.$fileName);
            $content = $cache->get('openstreetmap.'.$fileName, function(ItemInterface $item) {

                $item->expiresAfter(5);

                /** Получаем геоданные */
                $request = $this->freeHttpClient()
                    ->request(
                        'GET',
                        '/search',
                        ['query' => [
                            'q' => $this->latitude->getFloat().','.$this->longitude->getFloat(),
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
                            $this->latitude->getFloat().','.$this->longitude->getFloat(),
                        ],
                    );

                    return false;
                }

                $item->expiresAfter(DateInterval::createFromDateString('30 day'));

                return current($request->toArray(false));

            });

            if(empty($content))
            {
                return false;
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
                    'X-Secret' => $this->SECRET,
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
