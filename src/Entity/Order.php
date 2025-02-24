<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\OrderRepository;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: "orders")]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->date = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getUser(): ?User { return $this->user; }
    
    public function setUser(?User $user): static 
    { 
        $this->user = $user; 
        return $this; 
    }

    public function getDate(): ?\DateTimeInterface { return $this->date; }

    public function setDate(\DateTimeInterface $date): static 
    { 
        $this->date = $date; 
        return $this; 
    }

    public function getStatus(): ?string { return $this->status; }

    public function setStatus(string $status): static 
    { 
        $this->status = $status; 
        return $this; 
    }

    public function getOrderItems(): Collection { return $this->orderItems; }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }
        return $this;
    }

    public function getTotalPrice(): float
    {
        $total = 0;
        foreach ($this->orderItems as $item) {
            $total += $item->getProduct()->getPrix() * $item->getQuantity();
        }
        return $total;
    }
}
