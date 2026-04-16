<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientId', IntegerType::class, [        // ✅ was 'client_id'
                'label' => 'Client ID',
            ])
            ->add('orderType', ChoiceType::class, [        // ✅ was 'order_type'
                'label' => 'Order Type',
                'choices' => [
                    'Dine In'  => 'DINE_IN',
                    'Delivery' => 'DELIVERY',
                ],
            ])
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
                'choice_label' => function(Reservation $r) {
                    return sprintf('Res #%d — Client %d — %s %s',
                        $r->getReservationId(),
                        $r->getClientId(),
                        $r->getReservationDate()?->format('d/m/Y'),
                        $r->getReservationTime()?->format('H:i')
                    );
                },
                'label'       => 'Linked Reservation (optional)',
                'required'    => false,
                'placeholder' => '-- None --',
            ])
            ->add('deliveryAddress', TextType::class, [    // ✅ was 'delivery_address'
                'label'    => 'Delivery Address',
                'required' => false,
            ])
            ->add('totalAmount', NumberType::class, [      // ✅ was 'total_amount'
                'label' => 'Total Amount (TND)',
                'scale' => 2,
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Pending'   => 'PENDING',
                    'Prepared'  => 'PREPARED',
                    'Delivered' => 'DELIVERED',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Order::class]);
    }
}