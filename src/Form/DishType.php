<?php

namespace App\Form;

use App\Entity\Dish;
use App\Entity\Menu;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DishType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Dish name',
                'attr' => [
                    'placeholder' => 'e.g. Truffle Espresso Pancakes',
                    'class' => 'form-control',
                    'maxlength' => 120,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Short description of the dish',
                    'class' => 'form-control',
                    'maxlength' => 500,
                ],
            ])
            ->add('base_price', NumberType::class, [
                'label' => 'Base price (TND)',
                'scale' => 2,
                'html5' => true,
                'invalid_message' => 'Price must be a valid number (e.g. 12.50).',
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                    'pattern' => '^\\d+(\\.\\d{1,2})?$',
                ],
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Available',
                'required' => false,
            ])
            ->add('stock_quantity', IntegerType::class, [
                'label' => 'Stock quantity',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                ],
            ])
            ->add('image_url', UrlType::class, [
                'label' => 'Image URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://…',
                    'class' => 'form-control',
                    'maxlength' => 500,
                ],
            ])
            ->add('menu', EntityType::class, [
                'class' => Menu::class,
                'choice_label' => 'title',
                'label' => 'Menu',
                'placeholder' => $options['lock_menu'] ? null : 'Select menu',
                'disabled' => $options['lock_menu'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dish::class,
            'lock_menu' => false,
        ]);
        $resolver->setAllowedTypes('lock_menu', 'bool');
    }
}
