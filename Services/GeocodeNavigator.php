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

namespace BaksDev\Users\Address\Services;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use InvalidArgumentException;

final class GeocodeNavigator
{
    private ?GpsLatitude $latitude = null;

    private ?GpsLongitude $longitude = null;

    private array $locations = [];

    private GeocodeDistance $geocodeDistance;

    public function __construct(GeocodeDistance $geocodeDistance)
    {
        $this->geocodeDistance = $geocodeDistance;
    }

    public function withStart(GpsLatitude $latitude, GpsLongitude $longitude): self
    {
        //$this->start = ['latitude' => $latitude->getValue(), 'longitude' => $longitude->getValue()];

        $this->latitude = $latitude;
        $this->longitude = $longitude;

        return $this;
    }

    public function addGeocode(GpsLatitude $latitude, GpsLongitude $longitude, mixed $attr = null): self
    {
        if(!$this->latitude || !$this->longitude)
        {
            throw new InvalidArgumentException('Необходимо указать начало маршрута withStart');
        }

        $this->locations = array_merge($this->locations, [['latitude' => $latitude->getFloat(), 'longitude' => $longitude->getFloat(), 'attr' => $attr]]);

        return $this;
    }

    // Сортировка массива точек геолокации по возрастанию расстояния между ними
    public function getNavigate(): array
    {
        $locations = $this->locations;

        $counter = count($locations);

        $geocodeDistance = $this->geocodeDistance
            ->fromLatitude($this->latitude->getFloat())
            ->fromLongitude($this->longitude->getFloat());

        for($i = 1; $i < $counter; $i++)
        {
            $j = $i - 1;
            $temp = $locations[$i];

            $distance = $geocodeDistance
                ->toLatitude($locations[$i]['latitude'])
                ->toLongitude($locations[$i]['longitude'])
                ->getDistance();

            while(
                $j >= 0 &&
                $geocodeDistance
                    ->toLatitude($locations[$j]['latitude'])
                    ->toLongitude($locations[$j]['longitude'])
                    ->getDistance() > $distance
            )
            {
                $locations[$j + 1] = $locations[$j];
                $j--;
            }

            $locations[$j + 1] = $temp;

            //            $distance = $this->distance($locations[$i]['latitude'], $locations[$i]['longitude'], $start['latitude'], $start['lon']);
            //
            //            while ($j >= 0 && $this->distance($locations[$j]['latitude'], $locations[$j]['longitude'], $start['latitude'], $start['longitude']) > $distance)
            //            {
            //                $locations[$j + 1] = $locations[$j];
            //                $j--;
            //            }
            //
            //            $locations[$j + 1] = $temp;
        }

        $this->locations = $locations;
        return $this->locations;
    }

    /** Возвращает интервал от точки старта, по всему маршруту с возвратом к точке старта */
    public function getInterval(): int
    {
        /** Точка старта */
        $geocodeDistance = $this->geocodeDistance
            ->fromLatitude($this->latitude->getFloat())
            ->fromLongitude($this->longitude->getFloat());

        $all = 0;

        /* Рассчитываем дистанцию между точками */
        foreach($this->locations as $key => $location)
        {
            $distance = $geocodeDistance
                ->toLatitude($location['latitude'])
                ->toLongitude($location['longitude'])
                ->getDistanceRound();

            $all += $distance;

            $geocodeDistance = $this->geocodeDistance
                ->fromLatitude($location['latitude'])
                ->fromLongitude($location['longitude']);
        }

        /** Точка окончания */
        $distance = $geocodeDistance
            ->toLatitude($this->latitude->getFloat())
            ->toLongitude($this->longitude->getFloat())
            ->getDistanceRound();

        $all += $distance;

        return (int) round($all);
    }

    public function resetGeocode(): void
    {
        $this->locations = [];
    }

    // Функция для расчета расстояния между двумя точками геолокации
    private function distance($lat1, $lon1, $lat2, $lon2)
    {
        $earth_radius = 6371; // Радиус Земли в км
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Расстояние между точками в км

        return $earth_radius * $c;
    }
}
