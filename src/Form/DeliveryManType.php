<?php

namespace App\Form;

use App\Entity\DeliveryMan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class DeliveryManType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => false,
            ])
            ->add('vehicle_type', ChoiceType::class, [
                'label' => 'Vehicle Type',
                'required' => false,
                'choices' => [
                    'Motorcycle' => 'motorcycle',
                    'Car' => 'car',
                    'Bicycle' => 'bicycle',
                    'Scooter' => 'scooter',
                    'Van' => 'van',
                    'Truck' => 'truck',
                    'Other' => 'other'
                ],
                'placeholder' => 'Select vehicle type',
            ])
            ->add('vehicle_number', TextType::class, [
                'label' => 'Vehicle Number/License Plate',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => false,
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'On Leave' => 'on_leave',
                    'Suspended' => 'suspended'
                ],
                'placeholder' => 'Select status',
            ])
            ->add('address', TextType::class, [
                'label' => 'Address',
                'required' => false,
            ])
            ->add('salary', NumberType::class, [
                'label' => 'Salary',
                'required' => false,
                'scale' => 2,
            ])
            ->add('date_of_joining', DateType::class, [
                'label' => 'Date of Joining',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryMan::class,
        ]);
    }
}
