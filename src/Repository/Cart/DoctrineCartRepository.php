<?php

declare(strict_types = 1);

namespace App\Repository\Cart;

use App\Entity\Cart\Cart;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCartRepository implements CartRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function nextIdentity(): string
    {
        return \bin2hex(\random_bytes(16));
    }

    public function save(Cart $cart): void
    {
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }

    public function find(string $id): ?Cart
    {
        return $this->entityManager->find(Cart::class, $id);
    }
}
