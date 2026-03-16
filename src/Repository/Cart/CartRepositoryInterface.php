<?php

declare(strict_types = 1);

namespace App\Repository\Cart;

use App\Entity\Cart\Cart;

interface CartRepositoryInterface
{
    public function nextIdentity(): string;

    public function save(Cart $cart): void;

    public function find(string $id): ?Cart;
}
