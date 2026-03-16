<?php

declare(strict_types = 1);

namespace App\Controller\Api;

use App\Exception\Cart\CartNotFoundException;
use App\Exception\Cart\ProductNotFoundException;
use App\Service\Cart\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart')]
final class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
    ) {
    }

    #[Route('', name: 'api_cart_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $cart = $this->cartService->createCart();

        return new JsonResponse($this->normalizeCart($cart));
    }

    #[Route('/{id}', name: 'api_cart_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        if (\trim($id) === '') {
            return $this->errorResponse('Missing cart ID', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $cart = $this->cartService->getCart($id);
        } catch (CartNotFoundException $exception) {
            return $this->errorResponse($exception->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->normalizeCart($cart));
    }

    public function getMissing(): JsonResponse
    {
        return $this->get('');
    }

    #[Route('/add', name: 'api_cart_add_product', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = \json_decode((string) $request->getContent(), true);

        if (!\is_array($data)) {
            return $this->errorResponse(
                'Invalid JSON body',
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        $cartId = $data['cart_id'] ?? null;
        $sku = $data['sku'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!\is_string($cartId) || !\is_string($sku) || !\is_int($quantity)) {
            return $this->errorResponse('Missing required fields: cart_id and sku', JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($quantity < 1) {
            return $this->errorResponse('Quantity must be at least 1', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $cart = $this->cartService->addProduct($cartId, $sku, $quantity);
        } catch (CartNotFoundException | ProductNotFoundException $exception) {
            $statusCode = $exception instanceof ProductNotFoundException
                ? JsonResponse::HTTP_NOT_FOUND
                : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

            return $this->errorResponse($exception->getMessage(), $statusCode);
        } catch (\Throwable $throwable) {
            return $this->errorResponse($throwable->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->normalizeCart($cart));
    }

    #[Route('/remove', name: 'api_cart_remove_product', methods: ['POST'])]
    public function remove(Request $request): JsonResponse
    {
        $data = \json_decode((string) $request->getContent(), true);

        if (!\is_array($data)) {
            return $this->errorResponse(
                'Invalid JSON body',
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        $cartId = $data['cart_id'] ?? null;
        $sku = $data['sku'] ?? null;
        $quantity = $data['quantity'] ?? null;

        if (!\is_string($cartId) || !\is_string($sku)) {
            return $this->errorResponse('Missing required fields: cart_id and sku', JsonResponse::HTTP_BAD_REQUEST);
        }

        $quantityValue = null;

        if ($quantity !== null) {
            if (!\is_int($quantity)) {
                return $this->errorResponse(
                    'Quantity must be an integer when provided',
                    JsonResponse::HTTP_BAD_REQUEST,
                );
            }

            if ($quantity < 1) {
                return $this->errorResponse(
                    'Quantity must be at least 1 when provided',
                    JsonResponse::HTTP_BAD_REQUEST,
                );
            }

            $quantityValue = $quantity;
        }

        try {
            $cart = $this->cartService->removeProduct($cartId, $sku, $quantityValue);
        } catch (CartNotFoundException | ProductNotFoundException $exception) {
            return $this->errorResponse($exception->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $throwable) {
            return $this->errorResponse($throwable->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->normalizeCart($cart));
    }

    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse(['error' => $message], $statusCode);
    }

    /**
     * @return array{
     *     id: string,
     *     items: list<array{
     *         product: array{
     *             sku: string,
     *             name: string,
     *             price: float,
     *             description: string|null
     *         },
     *         quantity: int,
     *         total: float
     *     }>,
     *     item_count: int,
     *     total_quantity: int,
     *     total: float
     * }
     */
    private function normalizeCart(\App\Entity\Cart\Cart $cart): array
    {
        $items = [];

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            $items[] = [
                'product' => [
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'description' => $product->getDescription(),
                ],
                'quantity' => $item->getQuantity(),
                'total' => $item->getTotal(),
            ];
        }

        return [
            'id' => $cart->getId(),
            'items' => $items,
            'item_count' => $cart->getItemCount(),
            'total_quantity' => $cart->getTotalQuantity(),
            'total' => $cart->getTotal(),
        ];
    }
}
