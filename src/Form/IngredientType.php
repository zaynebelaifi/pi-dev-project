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
        $isEditMode = (bool) ($options['is_edit_mode'] ?? false);
        $today = (string) ($options['today'] ?? (new \DateTimeImmutable('today'))->format('Y-m-d'));

        $builder
            ->add('name', TextType::class, [
                'label' => 'Ingredient name',
                'required' => true,
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'e.g. Tomato',
                ],
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unit',
                'required' => true,
                'attr' => [
                    'maxlength' => 50,
                    'placeholder' => 'kg, L, unit',
                ],
            ])
            ->add('quantityInStock', NumberType::class, [
                'label' => 'Current quantity in stock',
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'disabled' => $isEditMode,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('minStockLevel', NumberType::class, [
                'label' => 'Minimum stock level',
                'required' => true,
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
                'required' => true,
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
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'min' => $today,
                ],
            ]);

        if ($isEditMode) {
            $builder->add('decreaseQuantity', NumberType::class, [
                'mapped' => false,
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'label' => 'Quantity to decrease',
                'empty_data' => '0',
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                    'placeholder' => '0.00',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ingredient::class,
            'is_edit_mode' => false,
            'today' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
        ]);

        $resolver->setAllowedTypes('is_edit_mode', 'bool');
        $resolver->setAllowedTypes('today', 'string');
    }
}
