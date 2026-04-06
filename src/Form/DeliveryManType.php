<?php

namespace App\Form;

use App\Entity\DeliveryMan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeliveryManType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('phone')
            ->add('email')
            ->add('vehicle_type')
            ->add('vehicle_number')
            ->add('status')
            ->add('address')
            ->add('salary')
            ->add('date_of_joining')
            ->add('rating')
            ->add('created_at')
            ->add('updated_at')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryMan::class,
        ]);
    }
}
