<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            new Product('SKU-001', 'Example Laptop', 999.99, 'Example high-end laptop'),
            new Product('SKU-002', 'Example Mouse', 29.99, 'Example ergonomic mouse'),
            new Product('SKU-003', 'Mechanical Keyboard', 129.99, 'Compact mechanical keyboard'),
        ];

        foreach ($products as $product) {
            $manager->persist($product);
        }

        $manager->flush();
    }
}

