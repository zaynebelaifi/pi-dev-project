<?php

namespace App\Form;

use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeliveryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('order_id')
            ->add('delivery_address')
            ->add('recipient_name')
            ->add('recipient_phone')
            ->add('pickup_location')
            ->add('status')
            ->add('scheduled_date')
            ->add('actual_delivery_date')
            ->add('estimated_time')
            ->add('current_latitude')
            ->add('current_longitude')
            ->add('delivery_notes')
            ->add('rating')
            ->add('created_at')
            ->add('updated_at')
            ->add('deliveryMan', EntityType::class, [
                'class' => DeliveryMan::class,
                'choice_label' => 'id',
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
