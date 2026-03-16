<?php

declare(strict_types=1);

namespace App\Entity\Cart;

use App\Entity\Product;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cart')]
class Cart
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $id;

    /**
     * @var Collection<string, CartItem>
     */
    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->items = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function addProduct(Product $product, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        foreach ($this->items as $item) {
            if ($item->getProduct()->getSku() === $product->getSku()) {
                $item->increaseQuantity($quantity);

                return;
            }
        }

        $this->items->add(new CartItem($this, $product, $quantity));
    }

    public function removeProduct(Product $product, ?int $quantity = null): void
    {
        foreach ($this->items as $item) {
            if ($item->getProduct()->getSku() !== $product->getSku()) {
                continue;
            }

            if ($quantity === null) {
                $this->items->removeElement($item);

                return;
            }

            $item->decreaseQuantity($quantity);

            if ($item->getQuantity() <= 0) {
                $this->items->removeElement($item);
            }

            return;
        }
    }

    public function getItemCount(): int
    {
        return $this->items->count();
    }

    public function getTotalQuantity(): int
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }

        return $total;
    }

    public function getTotal(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }
}

