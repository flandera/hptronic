<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Cart\Cart;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public const ORDER_ID = 'demo-order-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Cart|null $cart */
        $cart = $manager->getRepository(Cart::class)->find(CartFixtures::CART_ID);

        if ($cart === null) {
            // Cart not available: nothing to do, keep fixtures idempotent.
            return;
        }

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

        if ($items === []) {
            return;
        }

        $total = 0.0;

        foreach ($items as $item) {
            $total += $item->getTotal();
        }

        $order = new Order(
            self::ORDER_ID,
            new \DateTimeImmutable(),
            $items,
            $total,
            'Demo Street 1, Demo City',
        );

        foreach ($items as $item) {
            $item->setOrder($order);
        }

        $manager->persist($order);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
            CartFixtures::class,
        ];
    }
}

