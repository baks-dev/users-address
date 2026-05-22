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

use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use DateInterval;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class YandexMarketAddressRequest
{
    public function __construct(
        #[Target('usersAddressLogger')] private LoggerInterface $logger,
        private YandexMarketTokenRequest $tokenRequest
    ) {}

    /**
     * Позволяет узнать:
     * - координаты объекта по его адресу или названию
     * - узнать адрес объекта по его кординатам
     *
     * @see https://yandex.ru/maps-api/docs/geocoder-api/response.html
     */
    public function getAddress(string $address): GeocodeAddressDTO|false
    {
        if(empty($this->tokenRequest->getToken()))
        {
            return false;
        }

        $cache = new FilesystemAdapter('users-address');
        $fileName = md5($address);
        $cache->deleteItem($fileName);


        $content = $cache->get($fileName, function(ItemInterface $item) use ($address) {

            /* По умолчанию кешируем на 1 сек */
            $item->expiresAfter(DateInterval::createFromDateString('1 seconds'));

            $data = [
                'geocode' => $address, // Адрес либо географические координаты искомого объекта.
                'apikey' => $this->tokenRequest->getApikey(), // Ключ, полученный в Кабинете Разработчика.
                'format' => 'json', // Формат ответа геокодера
                'rspn' => 0, // Флаг, задающий ограничение поиска указанной областью.
                'lang' => $this->tokenRequest->getLangCountry(), // Язык ответа и региональные особенности карты.
                // signature - Подпись запроса.
            ];

            $request = $this->tokenRequest->getHttpClient()->request('GET', '/v1/', ['query' => $data]);

            $content = $request->getContent();

            if($request->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('users-address: Ошибка %s при определении адреса геолокации', $request->getStatusCode()),
                    [self::class.':'.__LINE__, $address, $content],
                );

                return false;
            }

            /* Кешируем результат на 30 дней */
            $item->expiresAfter(DateInterval::createFromDateString('30 days'));

            return $content;

        });

        if(false === $content || false === json_validate($content))
        {
            return false;
        }

        $result = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        $features = current($result->response->GeoObjectCollection->featureMember);

        $GeocoderMetaData = $features->GeoObject->metaDataProperty->GeocoderMetaData;
        $AddressDetails = $GeocoderMetaData->Address;

        /**
         * Заполняем объект результатом
         */

        $GeocodeAddressDTO = new GeocodeAddressDTO();

        $GeocodeAddressDTO->setAddress($AddressDetails->formatted);
        $GeocodeAddressDTO->setPostal($AddressDetails->postal_code ?? null);

        foreach($AddressDetails->Components as $component)
        {
            match ($component->kind)
            {
                "country" => $GeocodeAddressDTO->setCountry($component->name),
                "area" => false === empty($GeocodeAddressDTO->getArea()) ?: $GeocodeAddressDTO->setArea($component->name),
                "locality", "province" => $GeocodeAddressDTO->setLocality($component->name),
                "street" => $GeocodeAddressDTO->setStreet($component->name),
                "house" => $GeocodeAddressDTO->setHouse($component->name),
                default => null
            };
        }

        /**
         * Координаты
         */

        $arrCoordinates = explode(' ', $features->GeoObject->Point->pos);

        if(isset($arrCoordinates[1], $arrCoordinates[0]))
        {
            $GeocodeAddressDTO->setLatitude($arrCoordinates[1]);
            $GeocodeAddressDTO->setLongitude($arrCoordinates[0]);
        }

        return $GeocodeAddressDTO;
    }
}
