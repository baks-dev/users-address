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

namespace BaksDev\Users\Address\Services\Tests;

use BaksDev\Users\Address\Entity\GeocodeAddress;
use BaksDev\Users\Address\Services\GeocodeAddressParser;
use BaksDev\Users\Address\Type\Geocode\GeocodeAddressUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group geocode-address
 * @group geocode-address-parser-test
 */
#[When(env: 'test')]
class GeocodeAddressParserTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $main = $em->getRepository(GeocodeAddress::class)
            ->find(GeocodeAddressUid::TEST);

        if($main)
        {
            $em->remove($main);
        }

        $em->flush();
        $em->clear();
    }


    public function testUseCase(): void
    {
        /** @var GeocodeAddressParser $GeocodeAddressParser */

        $GeocodeAddressParser = self::getContainer()->get(GeocodeAddressParser::class);
        //$GeocodeAddress = $GeocodeAddressParser->getGeocode('Санкт-Петербург');
        $GeocodeAddress = $GeocodeAddressParser->getGeocode('Балашиха Пионерская 14');

        self::assertNotFalse($GeocodeAddress);

    }
}
