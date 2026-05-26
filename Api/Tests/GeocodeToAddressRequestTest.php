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

namespace BaksDev\Users\Address\Api\Tests;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Api\GeocodeToAddressRequest;
use BaksDev\Users\Address\UseCase\Geocode\GeocodeAddressDTO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('users-address')]
#[When(env: 'test')]
class GeocodeToAddressRequestTest extends KernelTestCase
{
    // data-latitude="55.627915" data-longitude="37.81628"
    // "geo_lat" => "56.3063854" "geo_lon" => "38.1502956"
    // "geo_lat" => "56.3063854" "geo_lon" => "38.1502956"

    private const float LATITUDE = 56.3063854;

    private const float LONGITUDE = 38.1502956;

    public function testUseCase(): void
    {
        /** @var GeocodeToAddressRequest $GeocodeToAddressRequest */
        $GeocodeToAddressRequest = self::getContainer()->get(GeocodeToAddressRequest::class);
        $GeocodeAddressDTO = $GeocodeToAddressRequest
            ->setLatitude(new GpsLatitude(self::LATITUDE))
            ->setLongitude(new GpsLongitude(self::LONGITUDE))
            ->find();

        self::assertInstanceOf(GeocodeAddressDTO::class, $GeocodeAddressDTO);

        // Вызываем все геттеры
        $reflectionClass = new ReflectionClass(GeocodeAddressDTO::class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach($methods as $method)
        {
            // Методы без аргументов
            if($method->getNumberOfParameters() === 0)
            {
                // Вызываем метод
                $data = $method->invoke($GeocodeAddressDTO);
                // dump($data);
            }
        }
    }
}
