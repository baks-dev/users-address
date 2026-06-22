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

namespace BaksDev\Users\Address\Api;

use BaksDev\Core\Type\UserAgent\UserAgentGenerator;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileByIdInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileById\UserProfileResult;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateInterval;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\Cache\ItemInterface;

#[Autoconfigure(public: true)]
final class AutoCompleteAddressRequest
{
    private string|false $address;

    private string $userAgent;

    public function __construct(
        #[Target('usersAddressLogger')] private readonly LoggerInterface $logger,
        #[Autowire(env: 'DADATA_KEY')] private readonly ?string $KEY = null,
    )
    {
        $UserAgentGenerator = new UserAgentGenerator();
        $this->userAgent = $UserAgentGenerator->genDesktop();
    }

    public function setAddress(false|string $address): self
    {
        $address = str_replace('~', '/', $address);

        // Разбиваем строку по запятым
        $parts = explode(',', $address);

        /* Удаляем подъезд|домофон|этаж из адреса */
        $filtered = array_filter(array_map('trim', $parts), static function($part) {
            // Пропускаем части, которые полностью состоят из "номер слово" или "слово номер"
            return !preg_match('/^\d+\s+(?:подъезд|домофон|этаж)$|^(?:подъезд|домофон|этаж)\s+\d+$/iu', $part);
        });

        $address = array_unique($filtered);
        $address = implode(', ', $address);

        $address = str_replace(
            ['дом', 'корпус'],
            ['д.', 'к.'],
            $address,
        );

        /** находит строение в адресе, например 4Ас3 и разделяет его на 4А с3 */
        $pattern = '/(\d+[А-Яа-я]?)([сС]\d+)/u';
        $address = preg_replace($pattern, '$1 $2', $address);

        $this->address = $address;

        return $this;
    }

    public function find(): array|false
    {
        if(empty($this->address))
        {
            throw new InvalidArgumentException('Invalid Argument Exception Address');
        }

        $cache = new FilesystemAdapter('users-address');
        $fileName = md5($this->address);

        //$cache->deleteItem('autocomplete.'.$fileName);
        return $cache->get('autocomplete.'.$fileName, function(ItemInterface $item) {

            $item->expiresAfter(DateInterval::createFromDateString('1 day'));

            if(empty($this->KEY))
            {
                return false;
            }

            try
            {
                /** Получаем геоданные */
                $request = $this->TokenHttpClient()
                    ->request(
                        'POST',
                        '/suggestions/api/4_1/rs/suggest/address',
                        ['json' => ['query' => $this->address]],
                    );

                $content = current($request->toArray(false));
            }
            catch(Exception $exception)
            {
                $this->logger->critical(
                    sprintf('users-address: Ошибка %s при получении геолокации', $exception->getMessage()),
                    [
                        self::class.':'.__LINE__,
                        $this->address,
                    ],
                );

                return false;
            }

            if($request->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('users-address: Ошибка %s при получении геолокации', $request->getStatusCode()),
                    [
                        self::class.':'.__LINE__,
                        $request->getContent(),
                        $this->address,
                    ],
                );

                return false;
            }

            $item->expiresAfter(DateInterval::createFromDateString('30 day'));

            return $content;

        });
    }

    private function TokenHttpClient(): RetryableHttpClient
    {
        return new RetryableHttpClient(
            HttpClient::create(['headers' =>
                [
                    'User-Agent' => $this->userAgent,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Token '.$this->KEY,
                ],
            ])
                ->withOptions([
                    'base_uri' => 'https://suggestions.dadata.ru/',
                    'verify_host' => false,
                ]),
        );
    }

}
