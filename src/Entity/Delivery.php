<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DeliveryRepository;

#[ORM\Entity(repositoryClass: DeliveryRepository::class)]
#[ORM\Table(name: 'delivery')]
class Delivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $delivery_id = null;

    public function getDelivery_id(): ?int
    {
        return $this->delivery_id;
    }

    public function setDelivery_id(int $delivery_id): self
    {
        $this->delivery_id = $delivery_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $order_id = null;

    public function getOrder_id(): ?int
    {
        return $this->order_id;
    }

    public function setOrder_id(int $order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: DeliveryMan::class, inversedBy: 'deliverys')]
    #[ORM\JoinColumn(name: 'delivery_man_id', referencedColumnName: 'delivery_man_id')]
    private ?DeliveryMan $deliveryMan = null;

    public function getDeliveryMan(): ?DeliveryMan
    {
        return $this->deliveryMan;
    }

    public function setDeliveryMan(?DeliveryMan $deliveryMan): self
    {
        $this->deliveryMan = $deliveryMan;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $delivery_address = null;

    public function getDelivery_address(): ?string
    {
        return $this->delivery_address;
    }

    public function setDelivery_address(string $delivery_address): self
    {
        $this->delivery_address = $delivery_address;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $recipient_name = null;

    public function getRecipient_name(): ?string
    {
        return $this->recipient_name;
    }

    public function setRecipient_name(?string $recipient_name): self
    {
        $this->recipient_name = $recipient_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $recipient_phone = null;

    public function getRecipient_phone(): ?string
    {
        return $this->recipient_phone;
    }

    public function setRecipient_phone(?string $recipient_phone): self
    {
        $this->recipient_phone = $recipient_phone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $pickup_location = null;

    public function getPickup_location(): ?string
    {
        return $this->pickup_location;
    }

    public function setPickup_location(?string $pickup_location): self
    {
        $this->pickup_location = $pickup_location;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduled_date = null;

    public function getScheduled_date(): ?\DateTimeInterface
    {
        return $this->scheduled_date;
    }

    public function setScheduled_date(?\DateTimeInterface $scheduled_date): self
    {
        $this->scheduled_date = $scheduled_date;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $actual_delivery_date = null;

    public function getActual_delivery_date(): ?\DateTimeInterface
    {
        return $this->actual_delivery_date;
    }

    public function setActual_delivery_date(?\DateTimeInterface $actual_delivery_date): self
    {
        $this->actual_delivery_date = $actual_delivery_date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $estimated_time = null;

    public function getEstimated_time(): ?int
    {
        return $this->estimated_time;
    }

    public function setEstimated_time(?int $estimated_time): self
    {
        $this->estimated_time = $estimated_time;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $current_latitude = null;

    public function getCurrent_latitude(): ?float
    {
        return $this->current_latitude;
    }

    public function setCurrent_latitude(?float $current_latitude): self
    {
        $this->current_latitude = $current_latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $current_longitude = null;

    public function getCurrent_longitude(): ?float
    {
        return $this->current_longitude;
    }

    public function setCurrent_longitude(?float $current_longitude): self
    {
        $this->current_longitude = $current_longitude;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $delivery_notes = null;

    public function getDelivery_notes(): ?string
    {
        return $this->delivery_notes;
    }

    public function setDelivery_notes(?string $delivery_notes): self
    {
        $this->delivery_notes = $delivery_notes;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rating = null;

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

}
