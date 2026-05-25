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

namespace BaksDev\Users\Address\Services;

use App\Kernel;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Api\AddressToGeocodeRequest;
use BaksDev\Users\Address\Api\GeocodeToAddressRequest;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Repository\AddressByGeocode\AddressByGeocodeInterface;
use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final readonly class GeocodeAddressParser
{
    public function __construct(
        #[Target('usersAddressLogger')] private LoggerInterface $logger,
        private AddressByGeocodeInterface $AddressByGeocodeRepository,
        private AddressToGeocodeRequest $AddressToGeocodeRequest,
        private GeocodeToAddressRequest $GeocodeToAddressRequest,
        private MessageDispatchInterface $messageDispatch,
    ) {}

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

        /** Если строка содержит геоданные */
        if(preg_match('/\d+\.\d+(,\s?)\d+\.\d+/', $address))
        {
            $geoData = explode(',', $address);
            $GpsLatitude = new GpsLatitude($geoData[0]);
            $GpsLongitude = new GpsLongitude($geoData[1]);


            /** делаем проверку по базе */
            $GeocodeAddress = $this->AddressByGeocodeRepository
                ->find(
                    latitude: $GpsLatitude,
                    longitude: $GpsLongitude,
                );

            if($GeocodeAddress instanceof GeocodeAddress)
            {
                $GeocodeAddress->getDto($GeocodeAddressDTO);
                return $GeocodeAddressDTO;
            }


            /**
             * Если по геолокации в базе не найдено - пробуем определить по API
             */

            $GeocodeAddressDTO = $this->GeocodeToAddressRequest
                ->setLatitude($GpsLatitude)
                ->setLongitude($GpsLongitude)
                ->find();

            if($GeocodeAddressDTO instanceof GeocodeAddressDTO)
            {
                /** Если найден адрес по геолокации - сохраняем */
                $this->messageDispatch->dispatch($GeocodeAddressDTO, transport: 'users-address');
                return $GeocodeAddressDTO;
            }
        }


        /** Если по геолокации не найдено - пробуем определить по адресу */
        $GeocodeAddressDTO = $this->AddressToGeocodeRequest
            ->setAddress($address)
            ->find();

        if(false === $GeocodeAddressDTO)
        {
            $this->logger->critical('users-address: Ошибка при получении геолокации адреса',
                [
                    self::class.':'.__LINE__,
                    $address,
                ],
            );

            /** Если при всех способах адрес не определили - возвращаем геолокацию проекта */
            return $this->AddressToGeocodeRequest->getAddressByProjectLocation();
        }

        /** Пробуем повторно определить адрес по геолокации в локальном хранилище */
        $GeocodeAddress = $this->AddressByGeocodeRepository->find(
            latitude: $GeocodeAddressDTO->getLatitude(),
            longitude: $GeocodeAddressDTO->getLongitude(),
        );

        if($GeocodeAddress instanceof GeocodeAddress)
        {
            $GeocodeAddress->getDto($GeocodeAddressDTO);
            return $GeocodeAddressDTO;
        }

        /** Если геолокацию по базе не нашли - сохраняем для последующих результатов */
        $this->messageDispatch->dispatch($GeocodeAddressDTO, transport: 'users-address');

        return $GeocodeAddressDTO;

    }
}
