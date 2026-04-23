<?php

namespace App\Form;

use App\Entity\FoodDonationEvent;
use App\Form\Model\EventItemAssignmentData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventItemAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event', EntityType::class, [
                'class' => FoodDonationEvent::class,
                'choice_label' => static function (FoodDonationEvent $event): string {
                    $name = (string) ($event->getCharityName() ?? 'Event');
                    $date = $event->getEventDate()?->format('M j, Y g:i A') ?? 'No date';

                    return sprintf('%s on %s', $name, $date);
                },
                'label' => 'Donation Event',
                'placeholder' => 'Choose an event',
            ])
            ->add('lines', CollectionType::class, [
                'entry_type' => EventItemAssignmentLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventItemAssignmentData::class,
        ]);
    }
}
