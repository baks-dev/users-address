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

namespace BaksDev\Users\Address\Api\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Address\Api\YandexMarketAddressRequest;
use BaksDev\Users\Address\Api\YandexMarketTokenRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group users-address
 */
#[When(env: 'test')]
class YandexMarketAddressRequestTest extends KernelTestCase
{
    private const ADDRESS = 'Балашиха Пионерская 14';

    public function testUseCase(): void
    {
        /** @var YandexMarketAddressRequest $YandexMarketAddressRequest */
        $YandexMarketAddressRequest = self::getContainer()->get(YandexMarketAddressRequest::class);
        $GeocodeAddressDTO = $YandexMarketAddressRequest->getAddress(self::ADDRESS);

        self::assertEquals('Московская область, Балашиха, микрорайон Железнодорожный, Пионерская улица, 14', $GeocodeAddressDTO->getAddress());

        self::assertEquals('55.741723', $GeocodeAddressDTO->getLatitude());
        self::assertEquals('38.025363', $GeocodeAddressDTO->getLongitude());

        self::assertEquals('Россия', $GeocodeAddressDTO->getCountry());
        self::assertEquals('143986', $GeocodeAddressDTO->getPostal());
        self::assertEquals('городской округ Балашиха', $GeocodeAddressDTO->getArea());
        self::assertEquals('Пионерская улица', $GeocodeAddressDTO->getStreet());
        self::assertEquals('14', $GeocodeAddressDTO->getHouse());

    }

}
