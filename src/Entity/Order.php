<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $order_id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    private ?int $client_id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'reservation_id', nullable: true, onDelete: 'SET NULL')]
    private ?Reservation $reservation = null;

    #[ORM\Column(type: 'string', length: 20, enumType: null)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['DINE_IN', 'DELIVERY'], message: 'Invalid order type.')]
    private ?string $order_type = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $order_date = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $delivery_address = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $total_amount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cart_items = null;

    // ✅ camelCase getters/setters — Symfony can now find them
    public function getOrderId(): ?int { return $this->order_id; }

    public function getClientId(): ?int { return $this->client_id; }
    public function setClientId(int $client_id): self { $this->client_id = $client_id; return $this; }

    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $reservation): self { $this->reservation = $reservation; return $this; }

    public function getOrderType(): ?string { return $this->order_type; }
    public function setOrderType(string $order_type): self { $this->order_type = $order_type; return $this; }

    public function getOrderDate(): ?\DateTimeInterface { return $this->order_date; }
    public function setOrderDate(\DateTimeInterface $order_date): self { $this->order_date = $order_date; return $this; }

    public function getDeliveryAddress(): ?string { return $this->delivery_address; }
    public function setDeliveryAddress(?string $delivery_address): self { $this->delivery_address = $delivery_address; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }

    public function getTotalAmount(): ?string { return $this->total_amount; }
    public function setTotalAmount(string $total_amount): self { $this->total_amount = $total_amount; return $this; }

    public function getCartItems(): ?string { return $this->cart_items; }
    public function setCartItems(?string $cart_items): self { $this->cart_items = $cart_items; return $this; }
}