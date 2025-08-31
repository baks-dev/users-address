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

namespace BaksDev\Users\Address\Services\Tests;

use BaksDev\Users\Address\Services\GeocodeDistance;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('users-address')]
#[When(env: 'test')]
final class GeocodeDistanceTest extends TestCase
{
    private GeocodeDistance $geocodeDistance;

    protected function setUp(): void
    {
        $this->geocodeDistance = new GeocodeDistance();
    }

    public function testIsEqualsTrue(): void
    {
        $geocode = $this->geocodeDistance
            ->fromLatitude(10)
            ->fromLongitude(20)
            ->toLatitude(10)
            ->toLongitude(20);

        $this->assertTrue($geocode->isEquals());
    }

    public function testIsEqualsFalse(): void
    {
        $geocode = $this->geocodeDistance
            ->fromLatitude(10)
            ->fromLongitude(20)
            ->toLatitude(30)
            ->toLongitude(40);

        $this->assertFalse($geocode->isEquals());
    }

    public function testGetDistanceNan(): void
    {
        $geocode = $this->geocodeDistance
            ->fromLatitude(10)
            ->fromLongitude(20)
            ->toLatitude(10)
            ->toLongitude(20);

        $this->assertEquals($geocode->getDistance(), 0,);

        //$this->assertTrue(is_nan($geocode->getDistance()));
    }

    public function testGetDistance(): void
    {
        $geocode = $this->geocodeDistance
            ->fromLatitude(55.7558)
            ->fromLongitude(37.6173)
            ->toLatitude(51.5074)
            ->toLongitude(0.1278);

        $distance = $geocode->getDistance();

        $this->assertEquals(2484.602267134038, $distance);
        $this->assertGreaterThan(2400, $distance);
        $this->assertLessThan(2500, $distance);
    }

    public function testGetDistanceRound(): void
    {
        $geocode = $this->geocodeDistance
            ->fromLatitude(55.7558)
            ->fromLongitude(37.6173)
            ->toLatitude(51.5074)
            ->toLongitude(0.1278);

        $distance = $geocode->getDistanceRound();

        $this->assertEquals(2485, $distance);
        $this->assertGreaterThan(2400, $distance);
        $this->assertLessThan(2500, $distance);
    }

    public function testValidateNotSetFromLongitude(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $geocode = $this->geocodeDistance
            ->fromLatitude(10)
            ->toLatitude(20)
            ->toLongitude(30);

        $geocode->validate();
    }

    public function testValidateNotSetFromLatitude(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $geocode = $this->geocodeDistance
            ->fromLongitude(10)
            ->toLatitude(20)
            ->toLongitude(30);

        $geocode->validate();
    }

    public function testValidateNotSetToLongitude(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $geocode = $this->geocodeDistance
            ->fromLatitude(10)
            ->fromLongitude(20)
            ->toLatitude(30);

        $geocode->validate();
    }

    public function testValidateNotSetToLatitude(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $geocode = $this->geocodeDistance
            ->fromLatitude(10)
            ->fromLongitude(20)
            ->toLongitude(30);

        $geocode->validate();
    }

}
