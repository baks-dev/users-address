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

namespace BaksDev\Users\Address\UseCase\Geocode;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Entity\GeocodeAddressInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see GeocodeAddress */
final class GeocodeAddressDTO implements GeocodeAddressInterface
{
    /** Широта */
    #[Assert\NotBlank]
    private GpsLatitude $latitude;

    /** Долгота*/
    #[Assert\NotBlank]
    private GpsLongitude $longitude;

    /** Страна */
    private ?string $country;

    /** Почтовый индекс */
    private ?string $postal;

    /** Область, регион */
    private ?string $area;

    /** Город */
    private ?string $locality;

    /** Улица */
    private ?string $street;

    /** Дом */
    private ?string $house;

    /** Полный адрес */
    private ?string $address;

    public function __construct($longitude, $latitude)
    {
        $this->latitude = new GpsLatitude($latitude);
        $this->longitude = new GpsLongitude($longitude);
    }

    /**
     * Долгота.
     */
    public function getLongitude(): GpsLongitude
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): void
    {
        $this->longitude = new GpsLongitude($longitude);
    }

    /**
     * Широта.
     */
    public function getLatitude(): GpsLatitude
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): void
    {
        $this->latitude = new GpsLatitude($latitude);
    }

    /** Страна */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    /** Область, регион */
    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): void
    {
        $this->area = $area;
    }

    /** Город */
    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(?string $locality): void
    {
        $this->locality = $locality;
    }

    /** Улица */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    /** Дом */
    public function getHouse(): ?string
    {
        return $this->house;
    }

    public function setHouse(?string $house): void
    {
        $this->house = $house;
    }

    /** Полный адрес */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /** Почтовый индекс */
    public function getPostal(): ?string
    {
        return $this->postal;
    }

    public function setPostal(?string $postal): void
    {
        $this->postal = $postal;
    }
}
