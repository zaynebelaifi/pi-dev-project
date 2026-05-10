<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'ratings')]
#[ORM\HasLifecycleCallbacks]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'rating_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FoodDonationEvent::class)]
    #[ORM\JoinColumn(name: 'donation_event_id', referencedColumnName: 'donation_event_id', nullable: false, onDelete: 'CASCADE')]
    private ?FoodDonationEvent $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'event_rating', type: 'integer')]
    #[Assert\NotNull(message: 'Event rating is required.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Event rating must be between {{ min }} and {{ max }}.')]
    private ?int $eventRating = null;

    #[ORM\Column(name: 'food_rating', type: 'integer')]
    #[Assert\NotNull(message: 'Food rating is required.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Food rating must be between {{ min }} and {{ max }}.')]
    private ?int $foodRating = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Comment cannot exceed {{ limit }} characters.')]
    private ?string $comment = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?FoodDonationEvent
    {
        return $this->event;
    }

    public function setEvent(?FoodDonationEvent $event): self
    {
        $this->event = $event;
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

    public function getEventRating(): ?int
    {
        return $this->eventRating;
    }

    public function setEventRating(?int $eventRating): self
    {
        $this->eventRating = $eventRating;
        return $this;
    }

    public function getFoodRating(): ?int
    {
        return $this->foodRating;
    }

    public function setFoodRating(?int $foodRating): self
    {
        $this->foodRating = $foodRating;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
