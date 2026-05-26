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
use BaksDev\Users\Address\Api\AutoCompleteAddressRequest;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileResult;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class AutocompleteController extends AbstractController
{
    #[Route('/autocomplete/{address}', name: 'user.autocomplete', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AutoCompleteAddressRequest $AutoCompleteAddressRequest,
        ?string $address = null,
    ): Response
    {
        if(!empty($address))
        {
            $address = strip_tags($address);
            $address = str_replace(['@', '#', '$', '%', '^', '!', '?', 'http://', 'https://'], '', $address);

            $result = $AutoCompleteAddressRequest
                ->setAddress($address)
                ->find();

            if($result)
            {
                $array = null;

                foreach($result as $value)
                {
                    $content = $value['data'];

                    if(empty($content['geo_lat']) || empty($content['geo_lon']))
                    {
                        continue;
                    }

                    $resAddress = null;

                    $resAddress[] = $content['country'];
                    $resAddress[] = $content['region'] ? ($content['region_type'] === 'г' ? 'г.'.$content['region'] : $content['region_with_type']) : null; // область

                    if($content['area'] !== $content['region'] && $content['area'] !== $content['city'])
                    {
                        $resAddress[] = $content['area'] ? $content['area_type'].'.'.$content['area'] : null; // город
                    }

                    if($content['city'] !== $content['region'])
                    {
                        $resAddress[] = $content['city'] ? $content['city_type'].'.'.$content['city'] : null; // город
                    }

                    $resAddress[] = $content['settlement'] ? $content['settlement_type'].''.$content['settlement'] : null; // поселок
                    $resAddress[] = $content['city_district'] ? $content['city_district_type'].'.'.$content['city_district'] : null; // район
                    $resAddress[] = $content['street_with_type'] ? $content['street_type'].($content['street_type'] === 'ул' ? '. ' : ' ').$content['street'] : null; // улица
                    $resAddress[] = $content['house'] ? $content['house_type'].'.'.$content['house'] : null; // дом
                    $resAddress[] = $content['flat'] ? $content['flat_type'].'.'.$content['flat'] : null; // дом

                    $cleanArray = array_filter($resAddress);

                    $array[] = [
                        'address' => implode(', ', $cleanArray),
                        'latitude' => $content['geo_lat'],
                        'longitude' => $content['geo_lon'],
                    ];
                }

                return new JsonResponse($array);
            }
        }

        return new JsonResponse(false, status: 404);
    }
}
