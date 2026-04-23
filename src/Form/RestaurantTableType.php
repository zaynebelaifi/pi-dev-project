<?php

namespace App\Form;

use App\Entity\RestaurantTable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class RestaurantTableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacity (seats)',
                'attr'  => ['min' => 1, 'max' => 8],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(message: 'Capacity must be at least 1.'),
                    new Assert\LessThanOrEqual(8, message: 'Capacity cannot exceed 8 seats.'),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Available' => 'AVAILABLE',
                    'Reserved'  => 'RESERVED',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RestaurantTable::class]);
    }
}
