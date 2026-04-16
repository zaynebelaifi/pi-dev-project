<?php

namespace App\Form;

use App\Entity\FoodDonationEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodDonationEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event_date', DateType::class, [
                'label' => 'Event Date',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('charity_name', TextType::class, [
                'label' => 'Charity Name',
            ])
            ->add('total_quantity', IntegerType::class, [
                'label' => 'Total Quantity',
                'attr' => ['min' => 1],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Scheduled' => 'SCHEDULED',
                    'Pending' => 'PENDING',
                    'Cancelled' => 'CANCELLED',
                    'Completed' => 'COMPLETED',
                ],
                'placeholder' => 'Select status',
                'required' => true,
                'empty_data' => 'PENDING',
            ])
            ->add('delivery_id', IntegerType::class, [
                'required' => false,
                'label' => 'Delivery (Optional)',
                'attr' => ['min' => 1],
            ])
            ->add('calendar_event_id', TextType::class, [
                'required' => false,
                'label' => 'Calendar Event ID (Optional)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodDonationEvent::class,
        ]);
    }
}
