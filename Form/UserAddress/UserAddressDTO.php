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

    /** Другие варианты заполнения */
    private ?array $autocomplete = null;

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

    public function setAddress(GeocodeAddress|GeocodeAddressUid $address): self
    {
        $this->address = $address instanceof GeocodeAddress ? $address->getId() : $address;
        return $this;
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

    public function setLongitude(string|GpsLongitude $longitude): self
    {
        $this->longitude = $longitude instanceof GpsLongitude ? $longitude : new GpsLongitude($longitude);
        return $this;
    }

    /** Широта */
    public function getLatitude(): ?GpsLatitude
    {
        return $this->latitude;
    }

    public function setLatitude(string|GpsLatitude $latitude): self
    {
        $this->latitude = $latitude instanceof GpsLatitude ? $latitude : new GpsLatitude($latitude);
        return $this;
    }

    /** Полный адрес */
    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function setDesc(?string $desc): self
    {
        $this->desc = $desc;
        return $this;
    }

    /** Флаг */
    public function isHouse(): bool
    {
        return $this->house;
    }

    /**
     * @param bool $house
     */
    public function setHouse(bool $house): self
    {
        $this->house = $house;
        return $this;
    }

    public function getAutocomplete(): ?array
    {
        return $this->autocomplete;
    }

    public function setAutocomplete(array|null|false $autocomplete): self
    {
        if(empty($autocomplete))
        {
            $this->autocomplete = null;
            return $this;
        }

        foreach($autocomplete as $key => $value)
        {
            $content = $value['data'];

            if(empty($content['geo_lat']) || empty($content['geo_lon']))
            {
                continue;
            }

            $resAddress = null;

            $resAddress[] = $content['country'];
            $resAddress[] = $content['region'] ? ($content['region_type'] === 'г' ? 'г.'.$content['region'] : $content['region_with_type']) : null; // область

            if($content['area'] !== $content['region'] && $content['area'] !== $content['city'] && $content['area_type'] !== $content['city_type'])
            {
                $resAddress[] = $content['area'] ? $content['area_type'].'.'.$content['area'] : null; // город
            }

            if($content['city'] !== $content['region'])
            {
                $resAddress[] = $content['city'] ? $content['city_type'].'.'.$content['city'] : null; // город
            }

            $resAddress[] = $content['settlement'] ? $content['settlement_type'].'.'.$content['settlement'] : null; // поселок, деревня, территория
            $resAddress[] = $content['city_district'] ? $content['city_district_type'].'.'.$content['city_district'] : null; // район
            $resAddress[] = $content['street_with_type'] ? $content['street_type'].(in_array($content['street_type'], ['ул', 'ш', 'пер']) ? '.' : ' ').$content['street'] : null; // улица

            $resAddress[] = $content['house'] ? str_replace('двлд', 'д', $content['house_type']).'.'.$content['house'] : null; // дом
            $resAddress[] = $content['flat'] ? $content['flat_type'].'.'.$content['flat'] : null; // дом
            $resAddress[] = $content['block'] ? $content['block_type'].$content['block'] : null; //  корпус

            $cleanArray = array_filter($resAddress);


            $this->autocomplete[$key]['value'] = implode(', ', $cleanArray);
            $this->autocomplete[$key]['latitude'] = $content['geo_lat'];
            $this->autocomplete[$key]['longitude'] = $content['geo_lon'];

        }

        return $this;
    }
}
