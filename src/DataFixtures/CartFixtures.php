<?php

declare(strict_types = 1);

namespace App\DataFixtures;

use App\Entity\Cart\Cart;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class CartFixtures extends Fixture implements DependentFixtureInterface
{
    public const CART_ID = 'demo-cart-1';

    public function load(ObjectManager $manager): void
    {
        /** @var Product|null $laptop */
        $laptop = $manager->getRepository(Product::class)->find('SKU-001');
        /** @var Product|null $mouse */
        $mouse = $manager->getRepository(Product::class)->find('SKU-002');

        if ($laptop === null || $mouse === null) {
            // Products not available: nothing to do, keep fixtures idempotent.
            return;
        }

        $cart = new Cart(self::CART_ID);
        $cart->addProduct($laptop, 1);
        $cart->addProduct($mouse, 2);

        $manager->persist($cart);
        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
        ];
    }
}
