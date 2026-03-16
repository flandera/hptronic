<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $id;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column]
    private float $total;

    #[ORM\Column(type: Types::TEXT)]
    private string $shippingAddress;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $geoLocation = null;

    public function __construct(
        string $id,
        \DateTimeImmutable $createdAt,
        array $items,
        float $total,
        string $shippingAddress,
        ?string $geoLocation = null,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->items = new ArrayCollection();
        foreach ($items as $item) {
            $this->items->add($item);
        }
        $this->total = $total;
        $this->shippingAddress = $shippingAddress;
        $this->geoLocation = $geoLocation;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function getGeoLocation(): ?string
    {
        return $this->geoLocation;
    }
}

