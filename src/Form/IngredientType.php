<?php

namespace App\Form;

use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ingredient name',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'e.g. Tomato',
                ],
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unit',
                'attr' => [
                    'maxlength' => 50,
                    'placeholder' => 'kg, L, unit',
                ],
            ])
            ->add('quantityInStock', NumberType::class, [
                'label' => 'Quantity in stock',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('minStockLevel', NumberType::class, [
                'label' => 'Minimum stock level',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('unitCost', NumberType::class, [
                'label' => 'Unit cost (TND)',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('expiryDate', DateType::class, [
                'label' => 'Expiry date',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ingredient::class,
        ]);
    }
}
