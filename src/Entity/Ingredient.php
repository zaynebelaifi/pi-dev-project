<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\IngredientRepository;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\Table(name: 'ingredient')]
class Ingredient
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

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Ingredient name is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Ingredient name cannot exceed {{ limit }} characters.')]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(name: 'quantityInStock', type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: 'Quantity in stock is required.')]
    #[Assert\PositiveOrZero(message: 'Quantity in stock must be 0 or greater.')]
    private ?float $quantityInStock = null;

    public function getQuantityInStock(): ?float
    {
        return $this->quantityInStock;
    }

    public function setQuantityInStock(?float $quantityInStock): self
    {
        $this->quantityInStock = $quantityInStock;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Unit is required.')]
    #[Assert\Length(max: 50, maxMessage: 'Unit cannot exceed {{ limit }} characters.')]
    private ?string $unit = null;

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    #[ORM\Column(name: 'createdAt', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[ORM\Column(name: 'minStockLevel', type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: 'Minimum stock level is required.')]
    #[Assert\PositiveOrZero(message: 'Minimum stock level must be 0 or greater.')]
    private ?float $minStockLevel = null;

    public function getMinStockLevel(): ?float
    {
        return $this->minStockLevel;
    }

    public function setMinStockLevel(?float $minStockLevel): self
    {
        $this->minStockLevel = $minStockLevel;
        return $this;
    }

    #[ORM\Column(name: 'unitCost', type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: 'Unit cost is required.')]
    #[Assert\PositiveOrZero(message: 'Unit cost must be 0 or greater.')]
    private ?float $unitCost = null;

    public function getUnitCost(): ?float
    {
        return $this->unitCost;
    }

    public function setUnitCost(?float $unitCost): self
    {
        $this->unitCost = $unitCost;
        return $this;
    }

    #[ORM\Column(name: 'expiryDate', type: 'date', nullable: false)]
    #[Assert\NotNull(message: 'Expiry date is required.')]
    private ?\DateTimeInterface $expiryDate = null;

    /**
     * @var Collection<int, DishIngredient>
     */
    #[ORM\OneToMany(mappedBy: 'ingredient', targetEntity: DishIngredient::class, orphanRemoval: true)]
    private Collection $dishIngredients;

    /**
     * @var Collection<int, Wasterecord>
     */
    #[ORM\OneToMany(mappedBy: 'ingredient', targetEntity: Wasterecord::class, orphanRemoval: true)]
    private Collection $wasteRecords;

    public function __construct()
    {
        $this->dishIngredients = new ArrayCollection();
        $this->wasteRecords = new ArrayCollection();
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    /**
     * @return Collection<int, DishIngredient>
     */
    public function getDishIngredients(): Collection
    {
        return $this->dishIngredients;
    }

    /**
     * @return Collection<int, Wasterecord>
     */
    public function getWasteRecords(): Collection
    {
        return $this->wasteRecords;
    }

}
