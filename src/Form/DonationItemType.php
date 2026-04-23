<?php

namespace App\Form;

use App\Entity\Dish;
use App\Entity\FoodDonationEvent;
use App\Entity\FoodDonationItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonationItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('donationEvent', EntityType::class, [
                'class' => FoodDonationEvent::class,
                'choice_label' => static function (FoodDonationEvent $event): string {
                    $name = (string) ($event->getCharityName() ?? 'Event');
                    $date = $event->getEventDate()?->format('Y-m-d H:i') ?? 'No date';

                    return sprintf('%s (%s)', $name, $date);
                },
                'label' => 'Select Event',
                'mapped' => false,
                'data' => $options['selected_event'],
                'placeholder' => 'Choose an event',
                'attr' => ['class' => 'donation-form-control'],
            ])
            ->add('item', EntityType::class, [
                'class' => Dish::class,
                'choice_label' => static function (Dish $dish): string {
                    return (string) ($dish->getName() ?? 'Unknown item');
                },
                'label' => 'Select Item',
                'mapped' => false,
                'data' => $options['selected_item'],
                'placeholder' => 'Choose an item',
                'attr' => ['class' => 'donation-form-control'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                    'class' => 'donation-form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodDonationItem::class,
            'selected_event' => null,
            'selected_item' => null,
        ]);

        $resolver->setAllowedTypes('selected_event', [FoodDonationEvent::class, 'null']);
        $resolver->setAllowedTypes('selected_item', [Dish::class, 'null']);
    }
}
