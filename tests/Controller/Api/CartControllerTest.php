<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\Api\CartController;
use App\Entity\Cart\Cart;
use App\Entity\Cart\CartItem;
use App\Entity\Product;
use App\Exception\Cart\CartNotFoundException;
use App\Exception\Cart\ProductNotFoundException;
use App\Service\Cart\CartService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class CartControllerTest extends TestCase
{
    private CartService&MockObject $cartService;

    private CartController $controller;

    protected function setUp(): void
    {
        $this->cartService = $this->createMock(CartService::class);
        $this->controller = new CartController($this->cartService);
    }

    public function testCreateReturnsNormalizedCart(): void
    {
        $cart = $this->createCartMock();

        $this->cartService
            ->expects(self::once())
            ->method('createCart')
            ->willReturn($cart);

        $response = $this->controller->create();

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);

        self::assertIsArray($data);
        self::assertSame('cart-id', $data['id']);
        self::assertSame(1, $data['item_count']);
        self::assertSame(2, $data['total_quantity']);
        self::assertEquals(20.0, $data['total']);
        self::assertCount(1, $data['items']);
        self::assertSame('SKU123', $data['items'][0]['product']['sku']);
    }

    public function testGetMissingReturnsBadRequest(): void
    {
        $response = $this->controller->getMissing();

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Missing cart ID', $data['error']);
    }

    public function testGetReturnsErrorWhenCartNotFound(): void
    {
        $this->cartService
            ->expects(self::once())
            ->method('getCart')
            ->with('missing-id')
            ->willThrowException(new CartNotFoundException('Cart with id "missing-id" not found.'));

        $response = $this->controller->get('missing-id');

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Cart with id "missing-id" not found.', $data['error']);
    }

    public function testGetReturnsCartOnSuccess(): void
    {
        $cart = $this->createCartMock();

        $this->cartService
            ->expects(self::once())
            ->method('getCart')
            ->with('cart-id')
            ->willReturn($cart);

        $response = $this->controller->get('cart-id');

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);

        self::assertSame('cart-id', $data['id']);
    }

    public function testAddReturnsBadRequestWhenBodyNotArray(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid-json');

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Invalid JSON body', $data['error']);
    }

    public function testAddReturnsBadRequestWhenMissingCartIdOrSku(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['cart_id' => 'cart-id']));

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Missing required fields: cart_id and sku', $data['error']);
    }

    public function testAddReturnsBadRequestWhenQuantityLessThanOne(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'SKU123',
            'quantity' => 0,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Quantity must be at least 1', $data['error']);
    }

    public function testAddReturnsNotFoundWhenProductNotFound(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'MISSING',
            'quantity' => 1,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->cartService
            ->expects(self::once())
            ->method('addProduct')
            ->with('cart-id', 'MISSING', 1)
            ->willThrowException(new ProductNotFoundException('Product with SKU "MISSING" not found.'));

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Product with SKU "MISSING" not found.', $data['error']);
    }

    public function testAddReturnsInternalServerErrorWhenCartNotFound(): void
    {
        $payload = [
            'cart_id' => 'missing-id',
            'sku' => 'SKU123',
            'quantity' => 1,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->cartService
            ->expects(self::once())
            ->method('addProduct')
            ->willThrowException(new CartNotFoundException('Cart with id "missing-id" not found.'));

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Cart with id "missing-id" not found.', $data['error']);
    }

    public function testAddReturnsInternalServerErrorOnUnexpectedError(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'SKU123',
            'quantity' => 1,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->cartService
            ->expects(self::once())
            ->method('addProduct')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Unexpected failure', $data['error']);
    }

    public function testAddReturnsCartOnSuccess(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'SKU123',
            'quantity' => 2,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));
        $cart = $this->createCartMock();

        $this->cartService
            ->expects(self::once())
            ->method('addProduct')
            ->with('cart-id', 'SKU123', 2)
            ->willReturn($cart);

        $response = $this->controller->add($request);

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('cart-id', $data['id']);
    }

    public function testRemoveReturnsBadRequestWhenBodyNotArray(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid-json');

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Invalid JSON body', $data['error']);
    }

    public function testRemoveReturnsBadRequestWhenMissingCartIdOrSku(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['cart_id' => 'cart-id']));

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Missing required fields: cart_id and sku', $data['error']);
    }

    public function testRemoveReturnsBadRequestWhenQuantityLessThanOne(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'SKU123',
            'quantity' => 0,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Quantity must be at least 1 when provided', $data['error']);
    }

    public function testRemoveReturnsInternalServerErrorWhenCartNotFound(): void
    {
        $payload = [
            'cart_id' => 'missing-id',
            'sku' => 'SKU123',
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->cartService
            ->expects(self::once())
            ->method('removeProduct')
            ->willThrowException(new CartNotFoundException('Cart with id "missing-id" not found.'));

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Cart with id "missing-id" not found.', $data['error']);
    }

    public function testRemoveReturnsInternalServerErrorWhenProductNotFound(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'MISSING',
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->cartService
            ->expects(self::once())
            ->method('removeProduct')
            ->willThrowException(new ProductNotFoundException('Product with SKU "MISSING" not found.'));

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Product with SKU "MISSING" not found.', $data['error']);
    }

    public function testRemoveReturnsInternalServerErrorOnUnexpectedError(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'SKU123',
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));

        $this->cartService
            ->expects(self::once())
            ->method('removeProduct')
            ->willThrowException(new \RuntimeException('Unexpected failure'));

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Unexpected failure', $data['error']);
    }

    public function testRemoveReturnsCartOnSuccess(): void
    {
        $payload = [
            'cart_id' => 'cart-id',
            'sku' => 'SKU123',
            'quantity' => 1,
        ];
        $request = new Request([], [], [], [], [], [], json_encode($payload));
        $cart = $this->createCartMock();

        $this->cartService
            ->expects(self::once())
            ->method('removeProduct')
            ->with('cart-id', 'SKU123', 1)
            ->willReturn($cart);

        $response = $this->controller->remove($request);

        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('cart-id', $data['id']);
    }

    /**
     * @return Cart&MockObject
     */
    private function createCartMock(): Cart
    {
        $product = $this->createMock(Product::class);
        $product
            ->method('getSku')
            ->willReturn('SKU123');
        $product
            ->method('getName')
            ->willReturn('Test Product');
        $product
            ->method('getPrice')
            ->willReturn(10.0);
        $product
            ->method('getDescription')
            ->willReturn('Description');

        $cartItem = $this->createMock(CartItem::class);
        $cartItem
            ->method('getProduct')
            ->willReturn($product);
        $cartItem
            ->method('getQuantity')
            ->willReturn(2);
        $cartItem
            ->method('getTotal')
            ->willReturn(20.0);

        $cart = $this->createMock(Cart::class);
        $cart
            ->method('getId')
            ->willReturn('cart-id');
        $cart
            ->method('getItems')
            ->willReturn([$cartItem]);
        $cart
            ->method('getItemCount')
            ->willReturn(1);
        $cart
            ->method('getTotalQuantity')
            ->willReturn(2);
        $cart
            ->method('getTotal')
            ->willReturn(20.0);

        return $cart;
    }
}

