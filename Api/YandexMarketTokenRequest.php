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

namespace BaksDev\Users\Address\Api;

use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Core\Type\UserAgent\UserAgentGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class YandexMarketTokenRequest
{
    private HttpClientInterface $httpClient;

    private Locale $local;

    private string $token;

    public function __construct(
        TranslatorInterface $translator,
        #[Autowire(env: 'MAPS_YANDEX_API')] private readonly string $apikey
    )
    {
        $this->local = new Locale($translator->getLocale());
    }

    public function getToken(): ?string
    {
        $UserAgentGenerator = new UserAgentGenerator();
        $userAgent = $UserAgentGenerator->genDesktop();

        $this->httpClient = HttpClient::create(['headers' => ['User-Agent' => $userAgent]])
            ->withOptions(['base_uri' => 'https://api-maps.yandex.ru']);

        /** Получаем конфиг  */
        $config = $this->httpClient->request(
            'GET',
            '/v3/',
            ['query' => [
                'apikey' => $this->apikey,
                'lang' => $this->getLangCountry()
            ]]
        );

        $config = $config->getContent();

        /** Получаем токен запроса */
        preg_match('/"token":"([^"]+)"/', $config, $matches);

        return $matches[1];
    }

    /**
     * HttpClient
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Метод возвращает региональность в формате в стандарте ISO 639-1
     * @example ru_RU
     */
    public function getLangCountry(): string
    {
        return $this->local->getLangCountry();
    }

    /**
     * Apikey
     */
    public function getApikey(): string
    {
        return $this->apikey;
    }
}
