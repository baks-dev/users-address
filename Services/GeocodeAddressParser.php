<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Users\Address\Services;

use App\Kernel;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Api\YandexMarketAddressRequest;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Repository\AddressByGeocode\AddressByGeocodeInterface;
use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GeocodeAddressParser
{
    //    private string $apikey;
    //
    //    //private GeocodeAddressHandler $handler;
    //
    //    private TranslatorInterface $translator;
    //
    private HttpClientInterface $httpClient;

    //private AddressByGeocodeInterface $addressByGeocode;
    private LoggerInterface $logger;

    public function __construct(
        #[Autowire(env: 'MAPS_YANDEX_API')] private readonly string $apikey,
        private readonly AddressByGeocodeInterface $addressByGeocode,
        private readonly YandexMarketAddressRequest $addressRequest,
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly TranslatorInterface $translator,
        LoggerInterface $usersAddressLogger
    )
    {

        $this->logger = $usersAddressLogger;

        //$this->handler = $handler;
        //$this->translator = $translator;
        //$this->apikey = $apikey;

        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5';
        $agentArray[] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36 OPR/49.0.2725.47';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/16.16299';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:52.0) Gecko/20100101 Firefox/52.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36 OPR/49.0.2725.64';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/62.0.3202.94 Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:56.0) Gecko/20100101 Firefox/56.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0';
        $agentArray[] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;  Trident/5.0)';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; rv:52.0) Gecko/20100101 Firefox/52.0';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/63.0.3239.84 Chrome/63.0.3239.84 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0;  Trident/5.0)';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0';
        $agentArray[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Safari/604.1.38';
        $agentArray[] = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
        $agentArray[] = 'Mozilla/5.0 (X11; CrOS x86_64 9901.77.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.97 Safari/537.36';

        $getArrayKey = array_rand($agentArray);

        $this->httpClient = HttpClient::create(['headers' => [
            'User-Agent' => $agentArray[$getArrayKey],
        ]])->withOptions(['base_uri' => 'https://api-maps.yandex.ru']);


        //$this->addressByGeocode = $addressByGeocode;

    }

    public function getGeocode(string $address): bool|GeocodeAddressDTO
    {

        $GeocodeAddressDTO = new GeocodeAddressDTO();

        //$address = 'Дзержинский, Денисьевский проезд, дом 17 стр. 1'; // https://yandex.ru/maps/?pt=37.81628,55.627915&z=18&l=map
        //$address = 'Дзержинский, Денисьевский проезд, дом 17'; // https://yandex.ru/maps/?pt=37.820268,55.628932&z=18&l=map
        //$address = 'Дзержинский, Денисьевский проезд'; // https://yandex.ru/maps/?pt=37.827832,55.63505&z=18&l=map
        //$address = 'Дзержинский'; //https://yandex.ru/maps/?pt=37.849616,55.630944&z=18&l=map
        //$address = 'Россия';
        //$address = 'fdfsdfdsfsdf54sdf4sdf';
        // Москва, Карельский бульвар 6к1 под

        /** Если строка содержит геоданные - делаем проверку по базе */
        if(preg_match('/\d+\.\d+(,\s?)\d+\.\d+/', $address))
        {
            $geoData = explode(',', $address);
            $GeocodeAddress = $this->addressByGeocode->find(new GpsLatitude($geoData[0]), new GpsLongitude($geoData[1]));

            if($GeocodeAddress instanceof GeocodeAddress)
            {
                $GeocodeAddress->getDto($GeocodeAddressDTO);
                return $GeocodeAddressDTO;
            }
        }

        /** Если по базе не найдено - пробуем определить по Яндекс-карте */
        $GeocodeAddressDTO = $this->addressRequest->getAddress($address);

        if(false === $GeocodeAddressDTO)
        {
            $this->logger->critical('users-address: Ошибка при получении геолокации адреса',
                [
                    __FILE__.':'.__LINE__,
                    $address
                ]
            );

            return false;
        }

        /** Пробуем повторно определить адрес по геолокации в локальном хранилище */
        $GeocodeAddress = $this->addressByGeocode->find(
            $GeocodeAddressDTO->getLatitude(),
            $GeocodeAddressDTO->getLongitude()
        );

        if($GeocodeAddress instanceof GeocodeAddress)
        {
            $GeocodeAddress->getDto($GeocodeAddressDTO);
            return $GeocodeAddressDTO;
        }

        /** Если адрес по геолокации не найден - сохраняем */
        $this->messageDispatch->dispatch($GeocodeAddressDTO, transport: 'users-address');

        return $GeocodeAddressDTO;


        //$GeocodeAddressDTO = new GeocodeAddressDTO($arrCoordinates[0], $arrCoordinates[1]);
        //        $GeocodeAddressDTO->setLatitude($arrCoordinates[1]);
        //        $GeocodeAddressDTO->setLongitude($arrCoordinates[0]);
        //        $GeocodeAddressDTO->setAddress($GeocoderMetaData->text); // Полный адрес
        //        $GeocodeAddressDTO->setCountry($AddressDetails->Country->CountryName); // Страна

        //        /* Если адрес региональный */
        //        if(isset($AddressDetails->Country->AdministrativeArea))
        //        {
        //
        //            /* Область, регион */
        //            if(isset($AddressDetails->Country->AdministrativeArea->AdministrativeAreaName))
        //            {
        //                $GeocodeAddressDTO->setArea($AddressDetails->Country->AdministrativeArea->AdministrativeAreaName);
        //            }
        //
        //            /* Город */
        //            if(isset($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->LocalityName))
        //            {
        //                $GeocodeAddressDTO->setLocality($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->LocalityName);
        //            }
        //
        //            if(isset($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare))
        //            {
        //                // Улица
        //                if(isset($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->ThoroughfareName))
        //                {
        //                    $GeocodeAddressDTO->setStreet($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->ThoroughfareName);
        //                }
        //
        //                if(isset($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->Premise))
        //                {
        //                    // почтовый индекс
        //                    if(isset($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->Premise->PostalCode->PostalCodeNumber))
        //                    {
        //                        $GeocodeAddressDTO->setPostal($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->Premise->PostalCode->PostalCodeNumber);
        //                    }
        //
        //                    // номер здания
        //                    if(isset($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->Premise->PremiseNumber))
        //                    {
        //                        $GeocodeAddressDTO->setHouse($AddressDetails->Country->AdministrativeArea->SubAdministrativeArea->Locality->Thoroughfare->Premise->PremiseNumber);
        //                    }
        //                }
        //            }
        //            elseif(isset($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare))
        //            {
        //                // Улица
        //                if(isset($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->ThoroughfareName))
        //                {
        //                    $GeocodeAddressDTO->setStreet($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->ThoroughfareName);
        //                }
        //
        //                if(isset($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise))
        //                {
        //                    // почтовый индекс
        //                    if(isset($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise->PostalCode->PostalCodeNumber))
        //                    {
        //                        $GeocodeAddressDTO->setPostal($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise->PostalCode->PostalCodeNumber);
        //                    }
        //
        //                    // номер здания
        //                    if(isset($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise->PremiseNumber))
        //                    {
        //                        $GeocodeAddressDTO->setHouse($AddressDetails->Country->AdministrativeArea->Locality->Thoroughfare->Premise->PremiseNumber);
        //                    }
        //                }
        //            }
        //        }


        //dd($GeocodeAddressDTO);


    }
}
