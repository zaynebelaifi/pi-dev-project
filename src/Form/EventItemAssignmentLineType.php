<?php

namespace App\Form;

use App\Entity\Dish;
use App\Form\Model\EventItemAssignmentLineData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventItemAssignmentLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('item', EntityType::class, [
                'class' => Dish::class,
                'choice_label' => static fn (Dish $dish): string => sprintf('%s (#%d)', (string) $dish->getName(), (int) $dish->getId()),
                'placeholder' => 'Choose an item',
                'label' => 'Item / Dish',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('assignmentId', HiddenType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventItemAssignmentLineData::class,
        ]);
    }
}
