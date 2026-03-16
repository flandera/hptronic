<?php

declare(strict_types = 1);

namespace App\Service\Order;

use App\Entity\Cart\Cart;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Exception\Order\OrderNotFoundException;
use App\Repository\Order\OrderRepositoryInterface;
use App\Service\Cart\CartService;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CartService $cartService,
    ) {
    }

    public function createOrderFromCart(string $cartId, string $shippingAddress): Order
    {
        $cart = $this->cartService->getCart($cartId);

        $items = $this->mapCartToOrderItems($cart);
        $total = 0.0;

        foreach ($items as $item) {
            $total += $item->getTotal();
        }

        $order = new Order(
            $this->orderRepository->nextIdentity(),
            new \DateTimeImmutable(),
            $items,
            $total,
            $shippingAddress,
        );

        foreach ($items as $item) {
            $item->setOrder($order);
        }

        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @return Order[]
     */
    public function getAllOrders(): array
    {
        return $this->orderRepository->findAll();
    }

    public function getOrder(string $id): Order
    {
        $order = $this->orderRepository->find($id);

        if ($order === null) {
            throw new OrderNotFoundException(\sprintf('Order with id "%s" not found.', $id));
        }

        return $order;
    }

    /**
     * @return list<OrderItem>
     */
    private function mapCartToOrderItems(Cart $cart): array
    {
        $items = [];

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $items[] = new OrderItem(
                $product->getSku(),
                $product->getName(),
                $product->getPrice(),
                $cartItem->getQuantity(),
            );
        }

        return $items;
    }
}
