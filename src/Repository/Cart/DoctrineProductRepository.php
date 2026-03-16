<?php

declare(strict_types=1);

namespace App\Repository\Cart;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findBySku(string $sku): ?Product
    {
        /** @var Product|null $product */
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->find($sku);

        return $product;
    }
}

