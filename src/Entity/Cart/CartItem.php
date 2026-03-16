<?php

declare(strict_types=1);

namespace App\Entity\Cart;

use App\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cart_item')]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Cart $cart;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_sku', referencedColumnName: 'sku', nullable: false)]
    private Product $product;

    #[ORM\Column]
    private int $quantity;

    public function __construct(Cart $cart, Product $product, int $quantity)
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->cart = $cart;
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function increaseQuantity(int $amount): void
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException('Increase amount must be at least 1.');
        }

        $this->quantity += $amount;
    }

    public function decreaseQuantity(int $amount): void
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException('Decrease amount must be at least 1.');
        }

        $this->quantity -= $amount;

        if ($this->quantity < 0) {
            $this->quantity = 0;
        }
    }

    public function getTotal(): float
    {
        return $this->product->getPrice() * $this->quantity;
    }
}

