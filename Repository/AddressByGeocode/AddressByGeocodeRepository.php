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

namespace BaksDev\Users\Address\Repository\AddressByGeocode;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Users\Address\Entity\GeocodeAddress;


final class AddressByGeocodeRepository implements AddressByGeocodeInterface
{
    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(ORMQueryBuilder $ORMQueryBuilder)
    {
        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }

    /**
     * Метод возвращает объект GeocodeAddress по геолокации
     */
    public function find(GpsLatitude $latitude, GpsLongitude $longitude): ?GeocodeAddress
    {
        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        // $select = sprintf('new %s(field.id)', Class::class);
        $orm
            ->select('geocode')
            ->from(GeocodeAddress::class, 'geocode');

        $orm
            ->where('geocode.latitude = :latitude')
            ->setParameter(
                'latitude',
                $latitude,
                GpsLatitude::TYPE
            );

        $orm
            ->andWhere('geocode.longitude = :longitude')
            ->setParameter(
                'longitude',
                $longitude,
                GpsLongitude::TYPE
            );

        return $orm->enableCache('users-address')->getOneOrNullResult();
    }
}