<?php

declare(strict_types=1);

namespace App\Repository\Order;

use App\Entity\Order\Order;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function nextIdentity(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Order::class)->findAll();
    }

    public function find(string $id): ?Order
    {
        return $this->entityManager->find(Order::class, $id);
    }
}

