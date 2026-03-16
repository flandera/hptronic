<?php

declare(strict_types = 1);

namespace App\Repository\Order;

use App\Entity\Order\Order;

interface OrderRepositoryInterface
{
    public function nextIdentity(): string;

    public function save(Order $order): void;

    /**
     * @return Order[]
     */
    public function findAll(): array;

    public function find(string $id): ?Order;
}
