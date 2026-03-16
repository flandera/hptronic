<?php

declare(strict_types=1);

namespace App\Controller\MapyCZ;

use App\Exception\Geo\MapyApiException;
use App\Service\Geo\MapyGeocodingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mapy')]
final class MapySuggestionController extends AbstractController
{
    public function __construct(
        private MapyGeocodingService $geocodingService,
    ) {
    }

    #[Route('/suggest', name: 'api_mapy_suggest', methods: ['GET'])]
    public function suggest(Request $request): JsonResponse
    {
        $query = (string) $request->query->get('q', '');
        $language = $request->query->get('lang');
        $limit = $request->query->getInt('limit', 5);

        if (trim($query) === '') {
            return new JsonResponse(
                ['error' => 'Missing query parameter "q".'],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        try {
            $result = $this->geocodingService->suggest($query, \is_string($language) ? $language : null, $limit);
        } catch (MapyApiException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_BAD_GATEWAY,
            );
        } catch (\Throwable $throwable) {
            return new JsonResponse(
                ['error' => $throwable->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse($result);
    }
}

