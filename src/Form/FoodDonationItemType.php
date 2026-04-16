<?php

namespace App\Form;

use App\Entity\FoodDonationItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodDonationItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('donation_event_id', ChoiceType::class, [
                'label' => 'Donation Event',
                'choices' => $options['event_choices'],
                'placeholder' => 'Select an event',
                'required' => true,
            ])
            ->add('item_id', ChoiceType::class, [
                'label' => 'Item / Dish',
                'choices' => $options['dish_choices'],
                'placeholder' => 'Select an item',
                'required' => true,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['min' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodDonationItem::class,
            'event_choices' => [],
            'dish_choices' => [],
        ]);

        $resolver->setAllowedTypes('event_choices', ['array']);
        $resolver->setAllowedTypes('dish_choices', ['array']);
    }
}
