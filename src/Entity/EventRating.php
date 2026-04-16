<?php

namespace App\Entity;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Repository\EventRatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRatingRepository::class)]
#[ORM\Table(name: 'ratings')]
#[ORM\HasLifecycleCallbacks]
class EventRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $rating_id = null;

    #[ORM\ManyToOne(targetEntity: FoodDonationEvent::class)]
    #[ORM\JoinColumn(name: 'donation_event_id', referencedColumnName: 'donation_event_id', nullable: false, onDelete: 'CASCADE')]
    private ?FoodDonationEvent $donationEvent = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    private ?int $event_rating = null;

    #[ORM\Column(type: 'integer')]
    private ?int $food_rating = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    public function getRating_id(): ?int
    {
        return $this->rating_id;
    }

    public function getDonationEvent(): ?FoodDonationEvent
    {
        return $this->donationEvent;
    }

    public function setDonationEvent(FoodDonationEvent $donationEvent): self
    {
        $this->donationEvent = $donationEvent;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent_rating(): ?int
    {
        return $this->event_rating;
    }

    public function setEvent_rating(int $event_rating): self
    {
        $this->event_rating = $event_rating;

        return $this;
    }

    public function getFood_rating(): ?int
    {
        return $this->food_rating;
    }

    public function setFood_rating(int $food_rating): self
    {
        $this->food_rating = $food_rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->created_at === null) {
            $this->created_at = new \DateTimeImmutable();
        }
    }

    public function getRatingId(): ?int
    {
        return $this->getRating_id();
    }

    public function getEventRating(): ?int
    {
        return $this->getEvent_rating();
    }

    public function setEventRating(int $eventRating): self
    {
        return $this->setEvent_rating($eventRating);
    }

    public function getFoodRating(): ?int
    {
        return $this->getFood_rating();
    }

    public function setFoodRating(int $foodRating): self
    {
        return $this->setFood_rating($foodRating);
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->getCreated_at();
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        return $this->setCreated_at($createdAt);
    }
}
