<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Meteo
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(
        HttpClientInterface $client,
        string $weatherApiKey
    ) {
        $this->client = $client;
        $this->apiKey = $weatherApiKey;
    }

    public function getWeather(string $city): array
    {
        $response = $this->client->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather',
            [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]
        );

        return $response->toArray();
    }
}
