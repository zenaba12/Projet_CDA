<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTimeImmutable;

class MongoDBService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function insertVisit(string $pageName)
    {
        $this->httpClient->request('POST', 'https://us-east-2.aws.neurelo.com/rest/visits/_one', [
            'headers' => [
                'X-API-KEY' => 'YTQtNDhlNC04NWMwLTE5ZDQwNGU4NzU0MSIsImdhdGV3YXlfaWQiOiJnd19iMmNhY2VhYi0yYTRlLTQ3YzYtOTlkZS1iNDM3M2I4NWE2MjIiLCJwb2xpY2llcyI6WyJSRUFEIiwiV1JJVEUiLCJVUERBVEUiLCJERUxFVEUiLCJDVVNUT00iXSwiaWF0IjoiMjAyNS0wMy0xNlQxNDowMTowOS40ODMyOTQxNjJaIiwianRpIjoiOGE0NDRmZTMtZDlmYi00ODYxLTk1MjUtZTdiMzc2ZmYwNTViIn0.gT9JU7bwsCGmUT--c5UGJXJClGLO2kWTZFPVFuasToD5r2eJQI_5j3qESwY53CymB-6RFZZHpZ5Frp0bXKQGXS08beTbw0-BN5JO8k0jvLONzFmO0JTPqKJ9szM606MkJDJm1rX8qixmLEBYk7qG8OtVmNSooEo0O0IKDsIHCV5UGIa41zAPNUTbGdzGMx-I_n-r4h0Fvrl1V_G7yz2OetWf9R5Zucr5XISTbNQrbXl4kcPQF_TqNMuZ4icOkgw7WOhL3Xoij-I2zMKZtZpFURNhTm7RXVlhPST6ZvghmAKWCEi4KHyiLSuvsEKCSHm_awCgFmquRpkjO2rc4a30rQ',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'pageName' => $pageName,
                'visitedAt' => (new DateTimeImmutable())->format("Y-m-d H:i:s"),
            ],
        ]);
    }
}
