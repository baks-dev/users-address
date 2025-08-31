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

namespace BaksDev\Users\Address\Controller\User\Tests;

use BaksDev\Users\User\Tests\TestUserAccount;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('users-address')]
#[When(env: 'test')]
final class GeocodeControllerTest extends WebTestCase
{
    private string $geocode = 'Балашиха Пионерская 14';

    private const string URL = '/geocode/%s';

    /** Доступ без роли */
    public function testGuestFiled(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $client->request('GET', sprintf(self::URL, $this->geocode));

            $statusCode = $client->getResponse()->getStatusCode();

            if($statusCode === 400)
            {
                $content = $client->getResponse()->getContent();
                $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

                self::assertEquals('danger', $content->type);
                self::assertEquals('Адрес местоположения', $content->header);
                self::assertEquals('Невозможно определить адрес местоположения', $content->message);
                self::assertEquals(400, $content->status);

                echo PHP_EOL.'Невозможно определить геолокацию. Возможно не указан токен авторизации MAPS_YANDEX_API'.PHP_EOL;

                return;
            }

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    /** Доступ по роли ROLE_ADMIN*/
    public function testRoleAdminSuccessful(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $usr = TestUserAccount::getAdmin();

            $client->setServerParameter('HTTP_USER_AGENT', $device);
            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, $this->geocode));


            $statusCode = $client->getResponse()->getStatusCode();

            if($statusCode === 400)
            {
                $content = $client->getResponse()->getContent();
                $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

                self::assertEquals('danger', $content->type);
                self::assertEquals('Адрес местоположения', $content->header);
                self::assertEquals('Невозможно определить адрес местоположения', $content->message);
                self::assertEquals(400, $content->status);

                echo PHP_EOL.'Невозможно определить геолокацию. Возможно не указан токен авторизации MAPS_YANDEX_API'.PHP_EOL;

                return;
            }

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    /** Доступ по роли ROLE_USER */
    public function testRoleUserDeny(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        foreach(TestUserAccount::getDevice() as $device)
        {
            $usr = TestUserAccount::getUsr();

            $client->setServerParameter('HTTP_USER_AGENT', $device);
            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, $this->geocode));

            $statusCode = $client->getResponse()->getStatusCode();

            if($statusCode === 400)
            {
                $content = $client->getResponse()->getContent();
                $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

                self::assertEquals('danger', $content->type);
                self::assertEquals('Адрес местоположения', $content->header);
                self::assertEquals('Невозможно определить адрес местоположения', $content->message);
                self::assertEquals(400, $content->status);

                echo PHP_EOL.'Невозможно определить геолокацию. Возможно не указан токен авторизации MAPS_YANDEX_API'.PHP_EOL;

                return;
            }

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }
}
