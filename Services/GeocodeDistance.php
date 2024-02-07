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

namespace BaksDev\Users\Address\Services;

use InvalidArgumentException;

final class GeocodeDistance
{
    /** Начальная точка отсчета  */
    private float $fromLatitude;

    private float $fromLongitude;

    /** Конечная точка отсчета */
    private float $toLatitude;

    private float $toLongitude;

    /** Начальная точка отсчета  */
    public function fromLatitude(float $code)
    {
        $this->fromLatitude = $code;
        return $this;
    }

    public function fromLongitude(float $code)
    {
        $this->fromLongitude = $code;
        return $this;
    }

    /** Конечная точка отсчета */
    public function toLatitude(float $code)
    {
        $this->toLatitude = $code;
        return $this;
    }

    public function toLongitude(float $code)
    {
        $this->toLongitude = $code;
        return $this;
    }

    public function isEquals() : bool
    {
        $this->validate();

        return $this->fromLatitude === $this->toLatitude && $this->fromLongitude === $this->toLongitude;
    }

    public function getDistance(): float
    {
        /*
         * Если начальная точка равна конечной точке - возвращаем NAN
         * для проверки можно использовать is_nan($result)
         */
//        if ($this->isEquals())
//        {
//            return NAN;
//        }

        if ($this->isEquals())
        {
            return 0;
        }


        $dLat = deg2rad($this->toLatitude - $this->fromLatitude);
        $dLon = deg2rad($this->toLongitude - $this->fromLongitude);


        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($this->fromLatitude)) * cos(deg2rad($this->toLatitude)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Расстояние между точками в км
        $earth_radius = 6371; // Радиус Земли в км
        return $earth_radius * $c;
    }

    /** Возвращает расстояние округляя её до целого числа */
    public function getDistanceRound(): int
    {
        /*
         * Если начальная точка равна конечной точке - возвращаем NAN
         * для проверки можно использовать is_nan($result)
         */
        if ($this->isEquals())
        {
            return 0;
        }

        $dLat = deg2rad($this->toLatitude - $this->fromLatitude);
        $dLon = deg2rad($this->toLongitude - $this->fromLongitude);


        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($this->fromLatitude)) * cos(deg2rad($this->toLatitude)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Расстояние между точками в км
        $earth_radius = 6371; // Радиус Земли в км
        $distance = $earth_radius * $c;
        return (int) round($distance);
    }


    public function validate() : void
    {
        if (
            empty($this->fromLongitude) ||
            empty($this->fromLatitude) ||
            empty($this->toLongitude) ||
            empty($this->toLatitude)
        ) {
            throw new InvalidArgumentException('Необходимо указать параметры расчета');
        }
    }
}
