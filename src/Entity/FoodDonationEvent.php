<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\FoodDonationEventRepository;

#[ORM\Entity(repositoryClass: FoodDonationEventRepository::class)]
#[ORM\Table(name: 'food_donation_event')]
#[ORM\HasLifecycleCallbacks]
class FoodDonationEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $donation_event_id = null;

    public function getDonation_event_id(): ?int
    {
        return $this->donation_event_id;
    }

    public function setDonation_event_id(int $donation_event_id): self
    {
        $this->donation_event_id = $donation_event_id;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotNull(message: 'Event date is required.')]
    private ?\DateTimeInterface $event_date = null;

    public function getEvent_date(): ?\DateTimeInterface
    {
        return $this->event_date;
    }

    public function setEvent_date(\DateTimeInterface $event_date): self
    {
        $this->event_date = $event_date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotNull(message: 'Total quantity is required.')]
    #[Assert\Positive(message: 'Total quantity must be greater than zero.')]
    private ?int $total_quantity = null;

    public function getTotal_quantity(): ?int
    {
        return $this->total_quantity;
    }

    public function setTotal_quantity(int $total_quantity): self
    {
        $this->total_quantity = $total_quantity;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'Charity name is required.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Charity name must be at least {{ limit }} characters.',
        maxMessage: 'Charity name cannot exceed {{ limit }} characters.'
    )]
    private ?string $charity_name = null;

    public function getCharity_name(): ?string
    {
        return $this->charity_name;
    }

    public function setCharity_name(string $charity_name): self
    {
        $this->charity_name = $charity_name;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Status is required.')]
    #[Assert\Choice(choices: ['SCHEDULED', 'PENDING', 'CANCELLED', 'COMPLETED'], message: 'Please select a valid status.')]
    private ?string $status = 'PENDING';

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'Delivery ID must be a positive number.')]
    private ?int $delivery_id = null;

    public function getDelivery_id(): ?int
    {
        return $this->delivery_id;
    }

    public function setDelivery_id(?int $delivery_id): self
    {
        $this->delivery_id = $delivery_id;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Calendar event ID cannot exceed {{ limit }} characters.'
    )]
    private ?string $calendar_event_id = null;

    public function getCalendar_event_id(): ?string
    {
        return $this->calendar_event_id;
    }

    public function setCalendar_event_id(?string $calendar_event_id): self
    {
        $this->calendar_event_id = $calendar_event_id;
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

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $now = new \DateTimeImmutable();
        if ($this->created_at === null) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getDonationEventId(): ?int
    {
        return $this->getDonation_event_id();
    }

    public function setDonationEventId(int $donation_event_id): self
    {
        return $this->setDonation_event_id($donation_event_id);
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->getEvent_date();
    }

    public function setEventDate(\DateTimeInterface $eventDate): self
    {
        return $this->setEvent_date($eventDate);
    }

    public function getTotalQuantity(): ?int
    {
        return $this->getTotal_quantity();
    }

    public function setTotalQuantity(int $totalQuantity): self
    {
        return $this->setTotal_quantity($totalQuantity);
    }

    public function getCharityName(): ?string
    {
        return $this->getCharity_name();
    }

    public function setCharityName(string $charityName): self
    {
        return $this->setCharity_name($charityName);
    }

    public function getDeliveryId(): ?int
    {
        return $this->getDelivery_id();
    }

    public function setDeliveryId(?int $deliveryId): self
    {
        return $this->setDelivery_id($deliveryId);
    }

    public function getCalendarEventId(): ?string
    {
        return $this->getCalendar_event_id();
    }

    public function setCalendarEventId(?string $calendarEventId): self
    {
        return $this->setCalendar_event_id($calendarEventId);
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->getCreated_at();
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        return $this->setCreated_at($createdAt);
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->getUpdated_at();
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        return $this->setUpdated_at($updatedAt);
    }
}
