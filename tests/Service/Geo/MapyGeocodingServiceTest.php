<?php

declare(strict_types = 1);

namespace App\Tests\Service\Geo;

use App\Exception\Geo\MapyApiException;
use App\Service\Geo\MapyGeocodingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MapyGeocodingServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    private MapyGeocodingService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service = new MapyGeocodingService(
            $this->httpClient,
            'test-api-key',
            'https://api.mapy.cz/v1',
        );
    }

    public function testConstructorThrowsWhenApiKeyEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MapyGeocodingService(
            $this->createMock(HttpClientInterface::class),
            '',
        );
    }

    public function testGeocodeThrowsWhenQueryEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->geocode('');
    }

    public function testGeocodeThrowsWhenLimitInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->geocode('Prague', null, 0);
    }

    public function testGeocodeReturnsDecodedArrayOnSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->expects(self::once())
            ->method('toArray')
            ->with(false)
            ->willReturn(['result' => 'ok']);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://api.mapy.cz/v1/geocode',
                [
                    'query' => [
                        'apikey' => 'test-api-key',
                        'query' => 'Prague',
                        'limit' => 5,
                    ],
                ],
            )
            ->willReturn($response);

        $result = $this->service->geocode('Prague');

        self::assertSame(['result' => 'ok'], $result);
    }

    public function testGeocodeIncludesOptionalLanguageAndLimit(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('toArray')
            ->with(false)
            ->willReturn(['result' => 'ok']);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://api.mapy.cz/v1/geocode',
                [
                    'query' => [
                        'apikey' => 'test-api-key',
                        'query' => 'Prague',
                        'lang' => 'cs',
                        'limit' => 10,
                    ],
                ],
            )
            ->willReturn($response);

        $result = $this->service->geocode('Prague', 'cs', 10);

        self::assertSame(['result' => 'ok'], $result);
    }

    public function testSuggestThrowsWhenQueryEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->suggest('');
    }

    public function testSuggestThrowsWhenLimitInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->suggest('Pra', null, 0);
    }

    public function testSuggestReturnsNormalizedSuggestionsOnSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->expects(self::once())
            ->method('toArray')
            ->with(false)
            ->willReturn([
                'items' => [
                    [
                        'name' => 'Dědinova',
                        'label' => 'Ulice',
                        'location' => 'Praha, Česko',
                    ],
                    [
                        'name' => 'Dědinsko',
                        'label' => 'Vesnice',
                        'location' => 'Střední Čechy',
                    ],
                ],
            ]);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://api.mapy.cz/v1/suggest',
                [
                    'query' => [
                        'apikey' => 'test-api-key',
                        'query' => 'Pra',
                        'limit' => 5,
                    ],
                ],
            )
            ->willReturn($response);

        $result = $this->service->suggest('Pra');

        self::assertSame(
            [
                'suggestions' => [
                    'Dědinova - Ulice - Praha, Česko',
                    'Dědinsko - Vesnice - Střední Čechy',
                ],
            ],
            $result,
        );
    }

    public function testSuggestIncludesOptionalLanguageAndLimit(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('toArray')
            ->with(false)
            ->willReturn(['items' => []]);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                'https://api.mapy.cz/v1/suggest',
                [
                    'query' => [
                        'apikey' => 'test-api-key',
                        'query' => 'Pra',
                        'lang' => 'cs',
                        'limit' => 10,
                    ],
                ],
            )
            ->willReturn($response);

        $result = $this->service->suggest('Pra', 'cs', 10);

        self::assertSame(['suggestions' => []], $result);
    }

    public function testRequestWrapsHttpClientExceptions(): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->willThrowException(new \RuntimeException('Network error'));

        $this->expectException(MapyApiException::class);

        $this->service->geocode('Prague');
    }

    public function testRequestThrowsOnNonSuccessfulStatusCode(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(500);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(MapyApiException::class);

        $this->service->geocode('Prague');
    }

    public function testDecodeJsonWrapsExceptions(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->expects(self::once())
            ->method('toArray')
            ->with(false)
            ->willThrowException(new \RuntimeException('Invalid JSON'));

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(MapyApiException::class);

        $this->service->geocode('Prague');
    }
}
