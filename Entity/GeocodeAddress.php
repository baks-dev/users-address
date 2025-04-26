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

namespace BaksDev\Users\Address\Entity;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Type\Geocode\GeocodeAddressUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* GeocodeAddress */

#[ORM\Entity]
#[ORM\Table(name: 'geocode_address')]
#[ORM\UniqueConstraint(columns: ['longitude', 'latitude'])]
#[ORM\Index(columns: ['address'])]
class GeocodeAddress extends EntityState
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: GeocodeAddressUid::TYPE)]
    private GeocodeAddressUid $id;

    /** Широта */
    #[Assert\NotBlank]
    #[ORM\Column(type: GpsLatitude::TYPE)]
    private GpsLatitude $latitude;

    /** Долгота*/
    #[Assert\NotBlank]
    #[ORM\Column(type: GpsLongitude::TYPE)]
    private GpsLongitude $longitude;

    /** Страна */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $country;

    /** Почтовый индекс */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $postal;

    /** Область, регион */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $area;

    /** Город */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $locality;

    /** Улица */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $street;

    /** Дом */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $house = null;

    /** Полный адрес */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $address;

    public function __construct()
    {
        $this->id = new GeocodeAddressUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): GeocodeAddressUid
    {
        return $this->id;
    }

    /** Долгота*/
    public function getLongitude(): GpsLongitude
    {
        return $this->longitude;
    }

    /** Широта */
    public function getLatitude(): GpsLatitude
    {
        return $this->latitude;
    }

    /** Полный адрес */
    public function getAddress(): ?string
    {
        return $this->address;
    }


    public function getHouse(): ?string
    {
        return $this->house;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof GeocodeAddressInterface || $dto instanceof self)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof GeocodeAddressInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}
