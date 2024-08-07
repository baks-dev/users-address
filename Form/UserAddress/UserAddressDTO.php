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

//namespace BaksDev\Users\Address\Form\UserAddress;
namespace BaksDev\Users\Address\Form\UserAddress;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Entity\UsersProfileAddressInterface;
use BaksDev\Users\Address\Type\Geocode\GeocodeAddressUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see UsersProfileAddress */
final class UserAddressDTO //implements UsersProfileAddressInterface
{
    /** Идентификатор адреса */
    #[Assert\NotBlank]
    private ?GeocodeAddressUid $address = null;

    /** Идентификатор профиля пользователя */
    //#[Assert\NotBlank]
//    #[Assert\Uuid]
//    private ?UserProfileUid $profile = null;

    /** Широта */
    #[Assert\NotBlank]
    private ?GpsLatitude $latitude; //= 55.627915;

    /** Долгота*/
    #[Assert\NotBlank]
    private ?GpsLongitude $longitude; // 37.816280

    /** Полный адрес */
    private ?string $desc = null;

    /** Флаг, что адрес является зданием */
    private bool $house = false;

    public function __construct()
    {
        $this->latitude = new GpsLatitude('55.627915');
        $this->longitude = new GpsLongitude('37.816280');
    }

    /** Идентификатор адреса */
    public function getAddress(): ?GeocodeAddressUid
    {
        return $this->address;
    }

    public function setAddress(GeocodeAddress|GeocodeAddressUid $address): void
    {
        $this->address = $address instanceof GeocodeAddress ? $address->getId() : $address;
    }

//    /** Идентификатор профиля пользователя */
//    public function getProfile(): UserProfileUid
//    {
//        return $this->profile;
//    }
//
//    public function setProfile(UserProfileUid $profile): void
//    {
//        $this->profile = $profile;
//    }

    /** Долгота*/
    public function getLongitude(): ?GpsLongitude
    {
        return $this->longitude;
    }

    public function setLongitude(string|GpsLongitude $longitude): void
    {
        $this->longitude = $longitude instanceof GpsLongitude ? $longitude : new GpsLongitude($longitude);
    }

    /** Широта */
    public function getLatitude(): ?GpsLatitude
    {
        return $this->latitude;
    }

    public function setLatitude(string|GpsLatitude $latitude): void
    {
        $this->latitude = $latitude instanceof GpsLatitude ? $latitude : new GpsLatitude($latitude);
    }

    /** Полный адрес */
    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function setDesc(?string $desc): void
    {
        $this->desc = $desc;
    }

    /** Флаг */
    public function isHouse(): bool
    {
        return $this->house;
    }

    /**
     * @param bool $house
     */
    public function setHouse(bool $house): void
    {
        $this->house = $house;
    }
}
