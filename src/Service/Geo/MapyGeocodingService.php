<?php

declare(strict_types = 1);

namespace App\Service\Geo;

use App\Exception\Geo\MapyApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MapyGeocodingService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $mapyApiKey,
        ?string $baseUrl = null,
    ) {
        if ($mapyApiKey === '') {
            throw new \InvalidArgumentException('MAPY API key must not be empty.');
        }

        $this->baseUrl = \rtrim($baseUrl ?? 'https://api.mapy.cz/v1', '/');
        $this->apiKey = $mapyApiKey;
    }

    /**
     * @return array<mixed>
     */
    public function geocode(string $query, ?string $language = null, int $limit = 5): array
    {
        if ($query === '') {
            throw new \InvalidArgumentException('Geocode query must not be empty.');
        }

        if ($limit <= 0) {
            throw new \InvalidArgumentException('Geocode limit must be greater than zero.');
        }

        $response = $this->request(
            'GET',
            \sprintf('%s/geocode', $this->baseUrl),
            [
                'query' => \array_filter(
                    [
                        'apikey' => $this->apiKey,
                        'query' => $query,
                        'lang' => $language,
                        'limit' => $limit,
                    ],
                    static fn (mixed $value): bool => $value !== null,
                ),
            ],
        );

        return $this->decodeJson($response);
    }

    /**
     * @return array<mixed>
     */
    public function suggest(string $query, ?string $language = null, int $limit = 5): array
    {
        if ($query === '') {
            throw new \InvalidArgumentException('Suggestion query must not be empty.');
        }

        if ($limit <= 0) {
            throw new \InvalidArgumentException('Suggestion limit must be greater than zero.');
        }

        $response = $this->request(
            'GET',
            \sprintf('%s/suggest', $this->baseUrl),
            [
                'query' => \array_filter(
                    [
                        'apikey' => $this->apiKey,
                        'query' => $query,
                        'lang' => $language,
                        'limit' => $limit,
                    ],
                    static fn (mixed $value): bool => $value !== null,
                ),
            ],
        );
        $data = $this->decodeJson($response);

        if (!\is_array($data['items'] ?? null)) {
            return ['suggestions' => []];
        }

        $suggestions = [];

        foreach ($data['items'] as $rawItem) {
            if (!\is_array($rawItem)) {
                continue;
            }

            /** @var array<string, mixed> $item */
            $item = $rawItem;

            $name = isset($item['name']) && \is_string($item['name']) ? $item['name'] : '';
            $label = isset($item['label']) && \is_string($item['label']) ? $item['label'] : '';
            $location = isset($item['location']) && \is_string($item['location']) ? $item['location'] : '';

            if ($name === '' && $location === '' && $label === '') {
                continue;
            }

            $parts = [];

            if ($name !== '') {
                $parts[] = $name;
            }

            if ($label !== '') {
                $parts[] = $label;
            }

            if ($location !== '') {
                $parts[] = $location;
            }

            $suggestions[] = \implode(' - ', $parts);
        }

        return ['suggestions' => $suggestions];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function request(string $method, string $url, array $options): ResponseInterface
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (\Throwable $throwable) {
            throw new MapyApiException(
                \sprintf('Failed to call Mapy API: %s', $throwable->getMessage()),
                0,
                $throwable,
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new MapyApiException(
                \sprintf('Mapy API returned HTTP %d.', $statusCode),
                $statusCode,
            );
        }

        return $response;
    }

    /**
     * @return array<mixed>
     */
    private function decodeJson(ResponseInterface $response): array
    {
        try {
            /** @var array<mixed> $data */
            $data = $response->toArray(false);
        } catch (\Throwable $throwable) {
            throw new MapyApiException(
                \sprintf('Failed to decode Mapy API response: %s', $throwable->getMessage()),
                0,
                $throwable,
            );
        }

        return $data;
    }
}
