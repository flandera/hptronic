<?php

declare(strict_types = 1);

namespace App\Repository\Cart;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findBySku(string $sku): ?Product;
}
