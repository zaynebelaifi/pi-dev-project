<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\RestaurantTable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Range;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientId', IntegerType::class, [
                'label' => 'Client ID',
                'disabled' => true,
                'attr' => [
                    'readonly' => 'readonly',
                ],
            ])
            ->add('table', EntityType::class, [
                'class' => RestaurantTable::class,
                'choice_label' => function (RestaurantTable $t) {
                    return sprintf('Table #%d — Capacity: %d (%s)', $t->getTableId(), $t->getCapacity(), $t->getStatus()); // ✅
                },
                'label' => 'Table',
                'placeholder' => '-- Select a table --',
            ])
            ->add('reservationDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('reservationTime', TimeType::class, [
                'label' => 'Time',
                'widget' => 'single_text',
            ])
            ->add('numberOfGuests', IntegerType::class, [
                'label' => 'Number of Guests',
                'attr' => [
                    'min' => 1,
                    'max' => 8,
                    'placeholder' => 'Enter between 1 and 8',
                ],
                'constraints' => [
                    new Range(min: 1, max: 8),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Confirmed' => 'CONFIRMED',
                    'Cancelled' => 'CANCELLED',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Reservation::class]);
    }
}