<?php

namespace App\Form;

use App\Entity\DishIngredient;
use App\Entity\Ingredient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DishIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ingredient', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => fn (Ingredient $ingredient) => sprintf('%s (%s)', $ingredient->getName(), $ingredient->getUnit()),
                'required' => true,
                'placeholder' => 'Select ingredient',
                'disabled' => $options['lock_ingredient'],
            ])
            ->add('quantityRequired', NumberType::class, [
                'label' => 'Quantity required',
                'required' => true,
                'scale' => 3,
                'html5' => true,
                'attr' => [
                    'min' => 0.001,
                    'step' => '0.001',
                    'inputmode' => 'decimal',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DishIngredient::class,
            'lock_ingredient' => false,
        ]);

        $resolver->setAllowedTypes('lock_ingredient', 'bool');
    }
}
