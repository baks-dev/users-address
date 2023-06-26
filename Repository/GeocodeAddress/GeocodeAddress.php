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

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class GeocodeAddress implements GeocodeAddressInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** Метод возвращает адрес и геолокацию  */
    public function fetchGeocodeAddressAssociative(GpsLatitude $latitude, GpsLongitude $longitude) : bool|array
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();

        $qb->select('geocode.longitude');
        $qb->addSelect('geocode.latitude');
        $qb->addSelect('geocode.address');

        $qb->from(\BaksDev\Users\Address\Entity\GeocodeAddress::TABLE, 'geocode');

        $qb->where('geocode.latitude = :latitude');
        $qb->setParameter('latitude', $latitude, GpsLatitude::TYPE);

        $qb->andWhere('geocode.longitude = :longitude');
        $qb->setParameter('longitude', $longitude, GpsLongitude::TYPE);


        /* Кешируем результат DBAL */

        $cacheFilesystem = new FilesystemAdapter('UsersAddress');

        $config = $this->entityManager->getConnection()->getConfiguration();
        $config?->setResultCache($cacheFilesystem);

        return $this->entityManager->getConnection()->executeCacheQuery(
            $qb->getSQL(),
            $qb->getParameters(),
            $qb->getParameterTypes(),
            new QueryCacheProfile(60 * 60 * 24)
        )->fetchAssociative();

    }


    /** Метод возвращает адрес и геолокацию по адресу */
    public function fetchGeocodeByAddressAssociative(string $address) : bool|array
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();

        $qb->select('geocode.longitude');
        $qb->addSelect('geocode.latitude');
        $qb->addSelect('geocode.address');

        $qb->from(\BaksDev\Users\Address\Entity\GeocodeAddress::TABLE, 'geocode');

        $qb->where('geocode.address = :address');
        $qb->setParameter('address', $address);

        //$qb->andWhere('geocode.longitude = :longitude');
        //$qb->setParameter('longitude', $longitude, GpsLongitude::TYPE);


        /* Кешируем результат DBAL */

        $cacheFilesystem = new FilesystemAdapter('UsersAddress');

        $config = $this->entityManager->getConnection()->getConfiguration();
        $config?->setResultCache($cacheFilesystem);

        return $this->entityManager->getConnection()->executeCacheQuery(
            $qb->getSQL(),
            $qb->getParameters(),
            $qb->getParameterTypes(),
            new QueryCacheProfile(60 * 60 * 24)
        )->fetchAssociative();

    }

}