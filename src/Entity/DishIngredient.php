<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\DishIngredientRepository;

#[ORM\Entity(repositoryClass: DishIngredientRepository::class)]
#[ORM\Table(name: 'dish_ingredient')]
class DishIngredient
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Dish::class, inversedBy: 'recipeLines')]
    #[ORM\JoinColumn(name: 'dish_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Dish $dish = null;

    public function getDish(): ?Dish
    {
        return $this->dish;
    }

    public function setDish(?Dish $dish): self
    {
        $this->dish = $dish;
        return $this;
    }

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: 'dishIngredients')]
    #[ORM\JoinColumn(name: 'ingredient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Ingredient $ingredient = null;

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(?Ingredient $ingredient): self
    {
        $this->ingredient = $ingredient;
        return $this;
    }

    #[ORM\Column(name: 'quantity_required', type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: 'Quantity required is mandatory.')]
    #[Assert\Positive(message: 'Quantity required must be greater than 0.')]
    private ?string $quantityRequired = null;

    public function getQuantityRequired(): ?float
    {
        return $this->quantityRequired !== null ? (float) $this->quantityRequired : null;
    }

    public function setQuantityRequired(?float $quantityRequired): self
    {
        $this->quantityRequired = $quantityRequired !== null ? (string) $quantityRequired : null;
        return $this;
    }

}
