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

namespace BaksDev\Users\Address\UseCase\Profiles\Tests;

use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Type\Geocode\GeocodeAddressUid;
use BaksDev\Users\Address\UseCase\Profiles\UsersProfileAddressDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid;

/** @group users-address */
#[When(env: 'test')]
final class UsersProfileAddressDTOTest extends TestCase
{
    private UsersProfileAddressDTO $dto;

    protected function setUp(): void
    {
        $this->dto = new UsersProfileAddressDTO();
    }


    public function testGetSetAddress(): void
    {
        $this->assertNull($this->dto->getAddress());

        $addressUid = new GeocodeAddressUid();
        $this->dto->setAddress($addressUid);
        $this::assertSame($addressUid, $this->dto->getAddress());

        $address = new GeocodeAddress();
        $this->dto->setAddress($address);
        $this::assertSame($address->getId(), $this->dto->getAddress());

        $this::assertIsObject($this->dto);
        $this::assertTrue(property_exists($this->dto, 'address'));

        /**
         * Тестируем атрибуты свойства
         * @see UsersProfileAddressDTO::$address
         */
        $attributes = (new ReflectionProperty($this->dto, 'address'))->getAttributes();
        $test = null;
        foreach ($attributes as $attribute)
        {
            $test[$attribute->getName()] = null;
        }

        $this::assertCount(1, $test);
        $this::assertArrayHasKey(NotBlank::class, $test);
    }

    public function testGetSetProfile(): void
    {
        $profileUid = new UserProfileUid();
        $this->dto->setProfile($profileUid);
        $this->assertSame($profileUid, $this->dto->getProfile());

        /**
         * Тестируем атрибуты свойства
         * @see UsersProfileAddressDTO::$profile
         */
        $attributes = (new ReflectionProperty($this->dto, 'profile'))->getAttributes();
        $test = null;
        foreach ($attributes as $attribute)
        {
            $test[$attribute->getName()] = null;
        }

        $this::assertCount(2, $test);
        $this::assertArrayHasKey(NotBlank::class, $test);

        $this->assertArrayHasKey(NotBlank::class, $test);
        $this->assertArrayHasKey(Uuid::class, $test);

    }

    public function testGetSetLongitude(): void
    {
        $this->assertSame('37.816280', $this->dto->getLongitude()->getValue());

        $this->dto->setLongitude('30,816280');
        $this->assertSame('30.816280', $this->dto->getLongitude()->getValue());


        $this->dto->setLongitude('30.816280');
        $this->assertSame('30.816280', $this->dto->getLongitude()->getValue());


        $this->expectException(InvalidArgumentException::class);
        $this->dto->setLongitude('30.0');


        /**
         * Тестируем атрибуты свойства
         * @see UsersProfileAddressDTO::$longitude
         */
        $attributes = (new ReflectionProperty($this->dto, 'longitude'))->getAttributes();
        $test = null;
        foreach ($attributes as $attribute)
        {
            $test[$attribute->getName()] = null;
        }

        $this::assertCount(1, $test);
        $this::assertArrayHasKey(NotBlank::class, $test);
    }

    public function testGetSetLatitude(): void
    {
        $this->assertSame('55.627915', $this->dto->getLatitude()->getValue());

        
        $this->dto->setLatitude('40,627910');
        $this->assertSame('40.627910', $this->dto->getLatitude()->getValue());

        $this->dto->setLatitude('40.627915');
        $this->assertSame('40.627915', $this->dto->getLatitude()->getValue());


        $this->expectException(InvalidArgumentException::class);
        $this->dto->setLatitude('40.0');


        /**
         * Тестируем атрибуты свойства
         * @see UsersProfileAddressDTO::$latitude
         */
        $attributes = (new ReflectionProperty($this->dto, 'latitude'))->getAttributes();
        $test = null;
        foreach ($attributes as $attribute)
        {
            $test[$attribute->getName()] = null;
        }

        $this::assertCount(1, $test);
        $this::assertArrayHasKey(NotBlank::class, $test);
    }

    public function testGetSetDesc(): void
    {
        $this->assertNull($this->dto->getDesc());

        $desc = 'Sample description';
        $this->dto->setDesc($desc);
        $this->assertSame($desc, $this->dto->getDesc());


        /**
         * Тестируем атрибуты свойства
         * @see UsersProfileAddressDTO::$desc
         */
        $attributes = (new ReflectionProperty($this->dto, 'desc'))->getAttributes();
        $test = null;
        foreach ($attributes as $attribute)
        {
            $test[$attribute->getName()] = null;
        }

        $this::assertNull($test);
    }

    public function testGetSetHouse(): void
    {
        $this->assertFalse($this->dto->isHouse());
        $this->dto->setHouse(true);
        $this->assertTrue($this->dto->isHouse());


        /**
         * Тестируем атрибуты свойства
         * @see UsersProfileAddressDTO::$house
         */
        $attributes = (new ReflectionProperty($this->dto, 'house'))->getAttributes();
        $test = null;
        foreach ($attributes as $attribute)
        {
            $test[$attribute->getName()] = null;
        }

        $this::assertNull($test);
    }
}
