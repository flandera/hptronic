<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\MapyCZ\MapySuggestionController;
use App\Exception\Geo\MapyApiException;
use App\Service\Geo\MapyGeocodingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class MapySuggestionControllerTest extends TestCase
{
    private MapyGeocodingService&MockObject $geocodingService;

    private MapySuggestionController $controller;

    protected function setUp(): void
    {
        $this->geocodingService = $this->createMock(MapyGeocodingService::class);
        $this->controller = new MapySuggestionController($this->geocodingService);
    }

    public function testSuggestReturnsBadRequestWhenQueryMissing(): void
    {
        $request = new Request();

        $response = $this->controller->suggest($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Missing query parameter "q".', $data['error']);
    }

    public function testSuggestReturnsSuggestionsOnSuccess(): void
    {
        $request = new Request(['q' => 'Pra', 'lang' => 'cs', 'limit' => 3]);

        $this->geocodingService
            ->expects(self::once())
            ->method('suggest')
            ->with('Pra', 'cs', 3)
            ->willReturn(['suggestions' => ['Prague']]);

        $response = $this->controller->suggest($request);

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame(['suggestions' => ['Prague']], $data);
    }

    public function testSuggestReturnsBadGatewayOnMapyApiError(): void
    {
        $request = new Request(['q' => 'Pra']);

        $this->geocodingService
            ->expects(self::once())
            ->method('suggest')
            ->willThrowException(new MapyApiException('Upstream error'));

        $response = $this->controller->suggest($request);

        self::assertSame(JsonResponse::HTTP_BAD_GATEWAY, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Upstream error', $data['error']);
    }

    public function testSuggestReturnsInternalServerErrorOnUnexpectedException(): void
    {
        $request = new Request(['q' => 'Pra']);

        $this->geocodingService
            ->expects(self::once())
            ->method('suggest')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $response = $this->controller->suggest($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Unexpected failure', $data['error']);
    }
}

