<?php

namespace App\Tests\Service;

use App\Service\Meteo;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MeteoTest extends TestCase
{
    public function testGetWeather(): void
    {
        $city = 'Paris';
        $apiKey = 'fake-api-key';

        // Données simulées renvoyées par l'API
        $fakeData = [
            'weather' => [
                ['description' => 'ciel dégagé']
            ],
            'main' => [
                'temp' => 18.5
            ],
            'name' => $city
        ];

        // Mock de ResponseInterface
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('toArray')
            ->willReturn($fakeData);

        // Mock de HttpClientInterface
        $clientMock = $this->createMock(HttpClientInterface::class);
        $clientMock
            ->method('request')
            ->with(
                'GET',
                'https://api.openweathermap.org/data/2.5/weather',
                $this->callback(function ($options) use ($city, $apiKey) {
                    return $options['query']['q'] === $city
                        && $options['query']['appid'] === $apiKey
                        && $options['query']['units'] === 'metric'
                        && $options['query']['lang'] === 'fr';
                })
            )
            ->willReturn($responseMock);

        // Instanciation du service avec le mock
        $meteoService = new Meteo($clientMock, $apiKey);

        $result = $meteoService->getWeather($city);

        $this->assertIsArray($result);
        $this->assertSame($fakeData, $result);
        $this->assertSame('Paris', $result['name']);
        $this->assertSame('ciel dégagé', $result['weather'][0]['description']);
        $this->assertSame(18.5, $result['main']['temp']);
    }
}
