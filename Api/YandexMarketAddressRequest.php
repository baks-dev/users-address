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
use Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class YandexMarketAddressRequest
{
    public function __construct(private YandexMarketTokenRequest $tokenRequest) {}

    public function getAddress(string $address): GeocodeAddressDTO|false
    {
        if(empty($this->tokenRequest->getToken()))
        {
            return false;
        }

        $cache = new FilesystemAdapter('users-address');
        $fileName = md5($address);

        /* Кешируем результат на 30 дней */
        $content = $cache->get($fileName, function(ItemInterface $item) use ($address) {

            $item->expiresAfter(86400 * 30);

            $token = $this->tokenRequest->getToken();

            $data = [
                'text' => $address,
                'token' => $token,
                'apikey' => $this->tokenRequest->getApikey(),
                'format' => 'json',
                'rspn' => 0,
                'lang' => $this->tokenRequest->getLangCountry(),
                'type' => 'geo',
                'properties' => 'addressdetails',
                'origin' => 'jsapi2Geocoder',
            ];

            try
            {
                /** Получаем геоданные */
                $result = $this->tokenRequest->getHttpClient()->request('GET', '/services/search/v2/', ['query' => $data]);
                $content = $result->getContent();
            }
            catch(Exception)
            {
                $item->expiresAfter(5);
                return false;
            }

            return $content;
        });

        if(false === $content || false === json_validate($content))
        {
            return false;
        }

        $result = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

        $features = current($result->features);
        $GeocoderMetaData = $features->properties->GeocoderMetaData;
        $arrCoordinates = $features->geometry->coordinates;

        $AddressDetails = $GeocoderMetaData->Address;

        $GeocodeAddressDTO = new GeocodeAddressDTO();

        if(isset($arrCoordinates[1], $arrCoordinates[0]))
        {
            $GeocodeAddressDTO->setLatitude($arrCoordinates[1]);
            $GeocodeAddressDTO->setLongitude($arrCoordinates[0]);
        }

        $GeocodeAddressDTO->setAddress($AddressDetails->formatted);
        $GeocodeAddressDTO->setPostal($AddressDetails->postal_code ?? null);

        foreach($AddressDetails->Components as $component)
        {
            match ($component->kind)
            {
                "country" => $GeocodeAddressDTO->setCountry($component->name),
                "area" => $GeocodeAddressDTO->setArea($component->name),
                "locality", "province" => $GeocodeAddressDTO->setLocality($component->name),
                "street" => $GeocodeAddressDTO->setStreet($component->name),
                "house" => $GeocodeAddressDTO->setHouse($component->name),
                default => null
            };
        }

        return $GeocodeAddressDTO;
    }
}
