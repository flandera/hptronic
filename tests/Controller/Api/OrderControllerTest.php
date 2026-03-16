<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\Api\OrderController;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Exception\Cart\CartNotFoundException;
use App\Exception\Order\OrderNotFoundException;
use App\Service\Order\OrderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class OrderControllerTest extends TestCase
{
    private OrderService&MockObject $orderService;

    private OrderController $controller;

    protected function setUp(): void
    {
        $this->orderService = $this->createMock(OrderService::class);
        $this->controller = new OrderController($this->orderService);
    }

    public function testCreateReturnsBadRequestWhenBodyNotArray(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid-json');

        $response = $this->controller->create($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Invalid JSON body', $data['error']);
    }

    public function testCreateReturnsBadRequestWhenMissingRequiredFields(): void
    {
        $payload = ['cart_id' => 'cart-id'];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $response = $this->controller->create($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Missing required fields: cart_id and shipping_address', $data['error']);
    }

    public function testCreateReturnsBadRequestWhenCartNotFound(): void
    {
        $payload = [
            'cart_id' => 'missing-id',
            'shipping_address' => 'Address',
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->orderService
            ->expects(self::once())
            ->method('createOrderFromCart')
            ->with('missing-id', 'Address')
            ->willThrowException(new CartNotFoundException('Cart with id "missing-id" not found.'));

        $response = $this->controller->create($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Cart with id "missing-id" not found.', $data['error']);
    }

    public function testCreateReturnsInternalServerErrorOnUnexpectedError(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'shipping_address' => 'Address',
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->orderService
            ->expects(self::once())
            ->method('createOrderFromCart')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $response = $this->controller->create($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Unexpected failure', $data['error']);
    }

    public function testCreateReturnsOrderOnSuccess(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'shipping_address' => 'Address',
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $order = $this->createOrderMock('order-id');

        $this->orderService
            ->expects(self::once())
            ->method('createOrderFromCart')
            ->with('cart-id', 'Address')
            ->willReturn($order);

        $response = $this->controller->create($request);

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('order-id', $data['id']);
    }

    public function testGetAllReturnsOrdersList(): void
    {
        $order1 = $this->createOrderMock('order-1');
        $order2 = $this->createOrderMock('order-2');

        $this->orderService
            ->expects(self::once())
            ->method('getAllOrders')
            ->willReturn([$order1, $order2]);

        $response = $this->controller->getAll();

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        self::assertArrayHasKey('orders', $data);
        self::assertCount(2, $data['orders']);
        self::assertSame('order-1', $data['orders'][0]['id']);
        self::assertSame('order-2', $data['orders'][1]['id']);
    }

    public function testGetReturnsBadRequestWhenIdEmpty(): void
    {
        $response = $this->controller->get('');

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Missing order ID', $data['error']);
    }

    public function testGetReturnsNotFoundWhenOrderNotFound(): void
    {
        $this->orderService
            ->expects(self::once())
            ->method('getOrder')
            ->with('missing-id')
            ->willThrowException(new OrderNotFoundException('Order with id "missing-id" not found.'));

        $response = $this->controller->get('missing-id');

        self::assertSame(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Order with id "missing-id" not found.', $data['error']);
    }

    public function testGetReturnsInternalServerErrorOnUnexpectedError(): void
    {
        $this->orderService
            ->expects(self::once())
            ->method('getOrder')
            ->with('order-id')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $response = $this->controller->get('order-id');

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Unexpected failure', $data['error']);
    }

    public function testGetReturnsOrderOnSuccess(): void
    {
        $order = $this->createOrderMock('order-id');

        $this->orderService
            ->expects(self::once())
            ->method('getOrder')
            ->with('order-id')
            ->willReturn($order);

        $response = $this->controller->get('order-id');

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('order-id', $data['id']);
    }

    /**
     * @return Order&MockObject
     */
    private function createOrderMock(string $id): Order
    {
        $item = $this->createMock(OrderItem::class);
        $item
            ->method('getSku')
            ->willReturn('SKU123');
        $item
            ->method('getName')
            ->willReturn('Test Product');
        $item
            ->method('getPrice')
            ->willReturn(10.0);
        $item
            ->method('getQuantity')
            ->willReturn(2);
        $item
            ->method('getTotal')
            ->willReturn(20.0);

        $order = $this->createMock(Order::class);
        $order
            ->method('getId')
            ->willReturn($id);
        $order
            ->method('getCreatedAt')
            ->willReturn(new \DateTimeImmutable('2024-01-01T00:00:00+00:00'));
        $order
            ->method('getItems')
            ->willReturn([$item]);
        $order
            ->method('getTotal')
            ->willReturn(20.0);
        $order
            ->method('getShippingAddress')
            ->willReturn('Address');
        $order
            ->method('getGeoLocation')
            ->willReturn('0,0');

        return $order;
    }
}

