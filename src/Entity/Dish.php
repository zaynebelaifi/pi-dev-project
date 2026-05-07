<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\DishRepository;

#[ORM\Entity(repositoryClass: DishRepository::class)]
#[ORM\Table(name: 'dish')]
#[ORM\HasLifecycleCallbacks]
class Dish
{
    private const DEFAULT_IMAGE_URL = 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=1200&q=80';

    public function __construct()
    {
        $this->recipeLines = new ArrayCollection();
        $this->donationEventItems = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'dishs')]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: 'Please select a menu.')]
    private ?Menu $menu = null;

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): self
    {
        $this->menu = $menu;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Dish name is required.')]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: 'Dish name must be at least {{ limit }} characters.',
        maxMessage: 'Dish name cannot exceed {{ limit }} characters.'
    )]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        if ($this->isAutoImageUrl($this->image_url)) {
            $this->image_url = $this->resolveAutoImageUrl();
        }

        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Description cannot exceed {{ limit }} characters.'
    )]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: 'Base price is required.')]
    #[Assert\PositiveOrZero(message: 'Base price must be 0 or greater.')]
    #[Assert\LessThanOrEqual(value: 9999.99, message: 'Base price is too high.')]
    private ?string $base_price = null;

    public function getBase_price(): ?float
    {
        return $this->base_price !== null ? (float) $this->base_price : null;
    }

    public function getBasePrice(): ?float
    {
        return $this->base_price !== null ? (float) $this->base_price : null;
    }

    public function setBase_price(?float $base_price): self
    {
        $this->base_price = $base_price !== null ? (string) $base_price : null;
        return $this;
    }

    public function setBasePrice(?float $basePrice): self
    {
        $this->base_price = $basePrice !== null ? (string) $basePrice : null;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $available = null;

    public function isAvailable(): ?bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Stock quantity must be 0 or greater.')]
    #[Assert\LessThanOrEqual(value: 100000, message: 'Stock quantity is too high.')]
    private ?int $stock_quantity = null;

    public function getStock_quantity(): ?int
    {
        return $this->stock_quantity;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stock_quantity;
    }

    public function setStock_quantity(?int $stock_quantity): self
    {
        $this->stock_quantity = $stock_quantity;
        return $this;
    }

    public function setStockQuantity(?int $stockQuantity): self
    {
        $this->stock_quantity = $stockQuantity;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Url(message: 'Please enter a valid image URL.')]
    #[Assert\Length(max: 500, maxMessage: 'Image URL cannot exceed {{ limit }} characters.')]
    private ?string $image_url = null;

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(?string $image_url): self
    {
        $normalized = $image_url !== null ? trim($image_url) : null;
        $this->image_url = $normalized !== null && $normalized != ''
            ? $normalized
            : $this->resolveAutoImageUrl();
        return $this;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        return $this->setImage_url($imageUrl);
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function ensureAutomaticImageUrl(): void
    {
        $normalized = $this->image_url !== null ? trim($this->image_url) : '';

        if ($normalized === '' || $this->isAutoImageUrl($normalized)) {
            $this->image_url = $this->resolveAutoImageUrl();
            return;
        }

        $this->image_url = $normalized;
    }

    private function resolveAutoImageUrl(): string
    {
        $dishName = strtolower(trim((string) $this->name));

        $keywordMap = [
            'pizza' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80',
            'pasta' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?auto=format&fit=crop&w=1200&q=80',
            'salad' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
            'soup' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=1200&q=80',
            'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80',
            'steak' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1200&q=80',
            'fish' => 'https://images.unsplash.com/photo-1485963631004-f2f00b1d6606?auto=format&fit=crop&w=1200&q=80',
            'sushi' => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=1200&q=80',
            'cake' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=1200&q=80',
            'dessert' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=1200&q=80',
            'coffee' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80',
            'chicken' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1200&q=80',
        ];

        foreach ($keywordMap as $keyword => $url) {
            if ($dishName !== '' && str_contains($dishName, $keyword)) {
                return $url;
            }
        }

        return self::DEFAULT_IMAGE_URL;
    }

    private function isAutoImageUrl(?string $imageUrl): bool
    {
        $normalized = $imageUrl !== null ? trim($imageUrl) : '';

        if ($normalized === '') {
            return true;
        }

        if ($normalized === '/images/default-dish.svg' || str_contains($normalized, 'placehold.co')) {
            return true;
        }

        return $normalized === self::DEFAULT_IMAGE_URL;
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

    /**
     * @var Collection<int, DishIngredient>
     */
    #[ORM\OneToMany(mappedBy: 'dish', targetEntity: DishIngredient::class, orphanRemoval: true)]
    private Collection $recipeLines;

    /**
     * @var Collection<int, DonationEventItem>
     */
    #[ORM\OneToMany(mappedBy: 'item', targetEntity: DonationEventItem::class, orphanRemoval: true)]
    private Collection $donationEventItems;

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    /**
     * @return Collection<int, DishIngredient>
     */
    public function getRecipeLines(): Collection
    {
        return $this->recipeLines;
    }

    public function addRecipeLine(DishIngredient $recipeLine): self
    {
        if (!$this->recipeLines->contains($recipeLine)) {
            $this->recipeLines->add($recipeLine);
            $recipeLine->setDish($this);
        }

        return $this;
    }

    public function removeRecipeLine(DishIngredient $recipeLine): self
    {
        if ($this->recipeLines->removeElement($recipeLine) && $recipeLine->getDish() === $this) {
            $recipeLine->setDish(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DonationEventItem>
     */
    public function getDonationEventItems(): Collection
    {
        return $this->donationEventItems;
    }

    public function addDonationEventItem(DonationEventItem $donationEventItem): self
    {
        if (!$this->donationEventItems->contains($donationEventItem)) {
            $this->donationEventItems->add($donationEventItem);
            $donationEventItem->setItem($this);
        }

        return $this;
    }

    public function removeDonationEventItem(DonationEventItem $donationEventItem): self
    {
        if ($this->donationEventItems->removeElement($donationEventItem)) {
            if ($donationEventItem->getItem() === $this) {
                $donationEventItem->setItem(null);
            }
        }

        return $this;
    }

}
