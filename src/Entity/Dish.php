<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\DishRepository;

#[ORM\Entity(repositoryClass: DishRepository::class)]
#[ORM\Table(name: 'dish')]
class Dish
{
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

    public function setName(string $name): self
    {
        $this->name = $name;
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
    private ?float $base_price = null;

    public function getBase_price(): ?float
    {
        return $this->base_price;
    }

    public function getBasePrice(): ?float
    {
        return $this->base_price;
    }

    public function setBase_price(float $base_price): self
    {
        $this->base_price = $base_price;
        return $this;
    }

    public function setBasePrice(float $basePrice): self
    {
        $this->base_price = $basePrice;
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
        $this->image_url = $image_url;
        return $this;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->image_url = $imageUrl;
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
