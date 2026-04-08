<?php

namespace App\Form;

use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DeliveryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('delivery_address', TextType::class, [
                'label' => 'Delivery Address',
                'required' => false,
            ])
            ->add('recipient_name', TextType::class, [
                'label' => 'Recipient Name',
                'required' => false,
            ])
            ->add('recipient_phone', TextType::class, [
                'label' => 'Recipient Phone',
                'required' => false,
            ])
            ->add('pickup_location', TextType::class, [
                'label' => 'Pickup Location',
                'required' => false,
            ])
            ->add('delivery_notes', TextareaType::class, [
                'label' => 'Delivery Notes',
                'required' => false,
            ])
            ->add('order_total', NumberType::class, [
                'label' => 'Order Total',
                'required' => false,
                'scale' => 2,
            ])
            ->add('license_plate', TextType::class, [
                'label' => 'License Plate (Tunisian Format)',
                'required' => false,
                'help' => 'Format: e.g., 123456AB789'
            ])
            ->add('fleetCar', EntityType::class, [
                'class' => FleetCar::class,
                'choice_label' => function(FleetCar $car) {
                    return sprintf('%s %s (%s)', $car->getMake(), $car->getModel(), $car->getLicense_plate());
                },
                'label' => 'Assigned Fleet Car',
                'required' => false,
                'placeholder' => '-- Select a car --'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Delivery::class,
        ]);
    }
}
