<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Entity\UsersProfileAddress;
use BaksDev\Users\Address\Services\GeocodeAddressParser;
use BaksDev\Users\Address\UseCase\Profiles\UsersProfileAddressDTO;
use BaksDev\Users\Address\UseCase\Profiles\UsersProfileAddressForm;
use BaksDev\Users\Address\UseCase\Profiles\UsersProfileAddressHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class GeocodeController extends AbstractController
{
    #[Route('/geocode/{address}', name: 'user.geocode', methods: ['GET', 'POST'])]
    public function index(
        GeocodeAddressParser $geocodeAddress,
        Request $request,
        EntityManagerInterface $entityManager,
        UsersProfileAddressHandler $addressHandler,
        string|null $address = null,
        //?string $address = null,
    ): Response {
//        if ($address === null)
//        {
//            return new Response(status: 404);
//        }

        $UsersProfileAddressDTO = new UsersProfileAddressDTO();
        $UsersProfileAddressDTO->setDesc($address);

        if (!empty($address))
        {
            /* Если передан идентификатор адреса */
            if (preg_match('{^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$}Di', $address))
            {
                $GeocodeAddress = $entityManager->getRepository(GeocodeAddress::class)->find($address);
            } elseif ($request->isMethod('GET'))
            {
                /** @var GeocodeAddress $var */
                $GeocodeAddress = $geocodeAddress->getGeocode($address);
            }

            $UsersProfileAddressDTO->setAddress($GeocodeAddress);
            $UsersProfileAddressDTO->setLatitude($GeocodeAddress->getLatitude());
            $UsersProfileAddressDTO->setLongitude($GeocodeAddress->getLongitude());
            $UsersProfileAddressDTO->setDesc($GeocodeAddress->getAddress());
            $UsersProfileAddressDTO->setHouse(($GeocodeAddress->getHouse() !== null));

            if ($this->getProfileUid())
            {
                $UsersProfileAddressDTO->setProfile($this->getProfileUid());
            }
        }

        $form = $this->createForm(UsersProfileAddressForm::class, $UsersProfileAddressDTO, [
            'action' => $this->generateUrl('UsersAddress:user.geocode', ['address' => $UsersProfileAddressDTO->getAddress()]),
        ]);

        $form->handleRequest($request);


        /*  */
        if ($form->isSubmitted() && $form->has('geocode'))
        {
            /* Если пользователь авторизован - прикрепляем адрес */
            if ($this->getProfileUid() && $form->isValid() )
            {
                $UsersProfileAddress = $addressHandler->handle($UsersProfileAddressDTO);

                if ($UsersProfileAddress instanceof UsersProfileAddress)
                {
                    return new JsonResponse(
                        [
                            'type' => 'success',
                            'header' => 'Ваш адрес',
                            'message' => $UsersProfileAddressDTO->getDesc(),
                            'status' => 200,
                        ]
                    );
                }

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

            return new JsonResponse(
                [
                    'type' => 'success',
                    'header' => 'Ваш адрес',
                    'message' => $UsersProfileAddressDTO->getDesc(),
                    'status' => 200,
                ]
            );
        }

        return $this->render([
            'form' => $form->createView(),
        ]);
    }
}
