<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Wasterecord;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WasterecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ingredient', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => fn (Ingredient $ingredient) => sprintf('%s (stock: %s %s)', $ingredient->getName(), rtrim(rtrim(number_format((float) $ingredient->getQuantityInStock(), 2, '.', ''), '0'), '.'), $ingredient->getUnit()),
                'placeholder' => 'Select ingredient',
            ])
            ->add('quantityWasted', NumberType::class, [
                'label' => 'Quantity wasted',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0.01,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                ],
            ])
            ->add('wasteType', ChoiceType::class, [
                'label' => 'Waste type',
                'choices' => [
                    'Expired' => 'Expired',
                    'Spoilage' => 'Spoilage',
                    'Preparation Loss' => 'Preparation Loss',
                    'Damage' => 'Damage',
                    'Other' => 'Other',
                ],
            ])
            ->add('reason', TextType::class, [
                'label' => 'Reason',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Short reason for waste entry',
                ],
            ])
            ->add('date', DateType::class, [
                'label' => 'Waste date',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Wasterecord::class,
        ]);
    }
}
