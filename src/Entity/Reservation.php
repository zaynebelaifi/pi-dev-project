<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'reservation_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'client_id', type: 'integer')]
    #[Assert\NotBlank(message: 'Client ID is required')]
    #[Assert\Positive(message: 'Client ID must be a positive number')]
    private int $clientId;

    #[ORM\ManyToOne(targetEntity: RestaurantTable::class)]
    #[ORM\JoinColumn(name: 'table_id', referencedColumnName: 'table_id', nullable: false)]
    #[Assert\NotNull(message: 'Please select a table')]
    private ?RestaurantTable $table = null;

    #[ORM\Column(name: 'reservation_date', type: 'date')]
    #[Assert\NotBlank(message: 'Reservation date is required')]
    private \DateTimeInterface $reservationDate;

    #[ORM\Column(name: 'reservation_time', type: 'time')]
    #[Assert\NotBlank(message: 'Reservation time is required')]
    private \DateTimeInterface $reservationTime;

    #[ORM\Column(name: 'number_of_guests', type: 'integer')]
    #[Assert\NotBlank(message: 'Number of guests is required')]
    #[Assert\Min(value: 1, message: 'Must have at least 1 guest')]
    #[Assert\Max(value: 8, message: 'Cannot exceed 8 guests')]
    private int $numberOfGuests;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: 'Status is required')]
    private string $status = 'CONFIRMED';

    public function getReservationId(): ?int { return $this->id; }

    public function getId(): ?int { return $this->id; }

    public function getClientId(): int { return $this->clientId; }
    public function setClientId(int $clientId): self { $this->clientId = $clientId; return $this; }

    public function getTableId(): ?int { return $this->table?->getTableId(); }

    public function setTableId(int $tableId): self
    {
        return $this;
    }

    public function getTable(): ?RestaurantTable { return $this->table; }
    public function setTable(?RestaurantTable $table): self { $this->table = $table; return $this; }

    public function getReservationDate(): \DateTimeInterface { return $this->reservationDate; }
    public function setReservationDate(\DateTimeInterface $date): self { $this->reservationDate = $date; return $this; }

    public function getReservationTime(): \DateTimeInterface { return $this->reservationTime; }
    public function setReservationTime(\DateTimeInterface $time): self { $this->reservationTime = $time; return $this; }

    public function getNumberOfGuests(): int { return $this->numberOfGuests; }
    public function setNumberOfGuests(int $n): self { $this->numberOfGuests = $n; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}