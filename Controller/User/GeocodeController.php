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

namespace BaksDev\Users\Address\Controller\User;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Api\YandexMarketAddressRequest;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Form\UserAddress\UserAddressDTO;
use BaksDev\Users\Address\Form\UserAddress\UserAddressForm;
use BaksDev\Users\Address\Repository\AddressByGeocode\AddressByGeocodeInterface;
use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GeocodeController extends AbstractController
{
    #[Route('/geocode/{address}', name: 'user.geocode', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        YandexMarketAddressRequest $geocodeAddress,
        AddressByGeocodeInterface $addressByGeocode,
        MessageDispatchInterface $messageDispatch,
        ?string $address = null,
    ): Response
    {

        $UsersProfileAddressDTO = new UserAddressDTO();
        $UsersProfileAddressDTO->setDesc($address);

        $GeocodeAddressDTO = new GeocodeAddressDTO();

        if(!empty($address))
        {
            $address = strip_tags($address);
            $address = str_replace(['@', '#', '$', '%', '^', '!', '?', 'http://', 'https://'], '', $address);

            /**
             * Если строка содержит геоданные - делаем проверку по базе
             */
            if(preg_match('/\d+\.\d+(,\s?)\d+\.\d+/', $address))
            {
                $geoData = explode(',', $address);
                $GeocodeAddress = $addressByGeocode->find(new GpsLatitude($geoData[0]), new GpsLongitude($geoData[1]));

                if($GeocodeAddress instanceof GeocodeAddress)
                {
                    $GeocodeAddress->getDto($GeocodeAddressDTO);
                }
            }

            /**
             * Если по базе геолокация не найдена - пробуем определить по API
             */
            if(empty($GeocodeAddressDTO->getAddress()))
            {
                $GeocodeAddressDTO = $geocodeAddress->getAddress($address);
            }

            /**
             * Если геолокация по адресу не найдена - ошибку JSON
             */
            if(empty($GeocodeAddressDTO) || empty($GeocodeAddressDTO->getAddress()))
            {
                return new JsonResponse(
                    [
                        'type' => 'danger',
                        'header' => 'Адрес местоположения',
                        'message' => 'Невозможно определить адрес местоположения',
                        'status' => 400,
                    ],
                    400
                );
            }

            /**
             * Если геолокация найдена - присваиваем геоданные пользовательской форме
             */
            $UsersProfileAddressDTO->setLatitude($GeocodeAddressDTO->getLatitude());
            $UsersProfileAddressDTO->setLongitude($GeocodeAddressDTO->getLongitude());
            $UsersProfileAddressDTO->setDesc($GeocodeAddressDTO->getAddress());
            $UsersProfileAddressDTO->setHouse(($GeocodeAddressDTO->getHouse() !== null));

            // Сохраняем в базу найденные геоданные для последующего выбора
            $messageDispatch->dispatch($GeocodeAddressDTO, transport: 'users-address');
        }


        $geo = sprintf('%s,%s', $UsersProfileAddressDTO->getLatitude(), $UsersProfileAddressDTO->getLongitude());

        $form = $this
            ->createForm(
                type: UserAddressForm::class,
                data: $UsersProfileAddressDTO,
                options: ['action' => $this->generateUrl('users-address:user.geocode', ['address' => $geo])]
            )
            ->handleRequest($request);

        /**
         * Если была отправлена форма и найдены геоданные - возвращаем JSON с адресом
         */
        if($form->isSubmitted() && $form->isValid() && $form->has('geocode'))
        {
            return new JsonResponse(
                [
                    'type' => 'success',
                    'header' => 'Ваш адрес',
                    'message' => $GeocodeAddressDTO->getAddress(),
                    'status' => 200,
                ]
            );
        }

        return $this->render(['form' => $form->createView()]);
    }
}
