<?php

namespace App\Form;

use App\Entity\FoodDonationEvent;
use App\Form\EventSubscriber\FoodDonationEventStatusSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodDonationEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event_date', DateTimeType::class, [
                'label' => 'Event Date & Time',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'with_seconds' => false,
                'model_timezone' => date_default_timezone_get(),
                'view_timezone' => date_default_timezone_get(),
                'invalid_message' => 'Event date/time cannot be in the past. Please choose a future date.',
                'attr' => [
                    'class' => 'js-event-datetime',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                    'step' => 60,
                ],
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
                    'Scheduled' => FoodDonationEvent::STATUS_SCHEDULED,
                    'In Progress' => FoodDonationEvent::STATUS_IN_PROGRESS,
                    'Ongoing' => FoodDonationEvent::STATUS_ONGOING,
                    'Completed' => FoodDonationEvent::STATUS_COMPLETED,
                    'Cancelled' => FoodDonationEvent::STATUS_CANCELLED,
                ],
                'required' => false,
                'placeholder' => 'Auto (calculated from event date/time)',
                'help' => 'Status is calculated automatically on submit. Only Cancelled is a manual override.',
                'empty_data' => '',
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
            ->addEventSubscriber(new FoodDonationEventStatusSubscriber())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodDonationEvent::class,
        ]);
    }
}
