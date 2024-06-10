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

namespace BaksDev\Users\Address\Repository\GeocodeAddress;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Orders\Order\Repository\GeocodeAddress\GeocodeAddressInterface;
use BaksDev\Users\Address\Entity\GeocodeAddress;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class GeocodeAddressRepository implements GeocodeAddressInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder)
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /** Метод возвращает адрес и геолокацию  */
    public function fetchGeocodeAddressAssociative(GpsLatitude $latitude, GpsLongitude $longitude): bool|array
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->select('geocode.longitude')
            ->addSelect('geocode.latitude')
            ->addSelect('geocode.address')
            ->from(GeocodeAddress::class, 'geocode');

        $qb
            ->where('geocode.latitude = :latitude')
            ->setParameter('latitude', $latitude, GpsLatitude::TYPE);

        $qb
            ->andWhere('geocode.longitude = :longitude')
            ->setParameter('longitude', $longitude, GpsLongitude::TYPE);

        return $qb->enableCache('users-address', 86400)->fetchAssociative();
    }


    /** Метод возвращает адрес и геолокацию по адресу */
    public function fetchGeocodeByAddressAssociative(string $address): bool|array
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->select('geocode.longitude')
            ->addSelect('geocode.latitude')
            ->addSelect('geocode.address')
            ->from(GeocodeAddress::class, 'geocode')
            ->where('geocode.address = :address')
            ->setParameter('address', $address);

        return $qb->enableCache('users-address', 86400)->fetchAssociative();

    }
}