<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exception\Cart\CartNotFoundException;
use App\Exception\Order\OrderNotFoundException;
use App\Service\Order\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
final class OrderController extends AbstractController
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);

        if (!\is_array($data)) {
            return $this->errorResponse('Invalid JSON body', JsonResponse::HTTP_BAD_REQUEST);
        }

        $cartId = $data['cart_id'] ?? null;
        $shippingAddress = $data['shipping_address'] ?? null;

        if (!\is_string($cartId) || !\is_string($shippingAddress) || $cartId === '' || $shippingAddress === '') {
            return $this->errorResponse('Missing required fields: cart_id and shipping_address', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $order = $this->orderService->createOrderFromCart($cartId, $shippingAddress);
        } catch (CartNotFoundException $exception) {
            return $this->errorResponse($exception->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Throwable $throwable) {
            return $this->errorResponse($throwable->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->normalizeOrder($order));
    }

    #[Route('', name: 'api_orders_get_all', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        $orders = $this->orderService->getAllOrders();

        $normalized = array_map(
            fn (\App\Entity\Order\Order $order): array => $this->normalizeOrder($order),
            $orders,
        );

        return new JsonResponse(['orders' => $normalized]);
    }

    #[Route('/{id}', name: 'api_orders_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        if (trim($id) === '') {
            return $this->errorResponse('Missing order ID', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $order = $this->orderService->getOrder($id);
        } catch (OrderNotFoundException $exception) {
            return $this->errorResponse($exception->getMessage(), JsonResponse::HTTP_NOT_FOUND);
        } catch (\Throwable $throwable) {
            return $this->errorResponse($throwable->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->normalizeOrder($order));
    }

    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse(['error' => $message], $statusCode);
    }

    private function normalizeOrder(\App\Entity\Order\Order $order): array
    {
        $items = [];

        foreach ($order->getItems() as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'quantity' => $item->getQuantity(),
                'total' => $item->getTotal(),
            ];
        }

        return [
            'id' => $order->getId(),
            'created_at' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'items' => $items,
            'total' => $order->getTotal(),
            'shipping_address' => $order->getShippingAddress(),
            'geo_location' => $order->getGeoLocation(),
        ];
    }
}

