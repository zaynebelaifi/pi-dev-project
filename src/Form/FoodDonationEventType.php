<?php

namespace App\Form;

use App\Entity\FoodDonationEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodDonationEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event_date')
            ->add('total_quantity')
            ->add('charity_name')
            ->add('status')
            ->add('delivery_id')
            ->add('calendar_event_id')
            ->add('created_at')
            ->add('updated_at')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodDonationEvent::class,
        ]);
    }
}
