<?php

namespace App\Form;

use App\Entity\FleetCar;
use App\Entity\DeliveryMan;
use App\Repository\DeliveryManRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FleetCarType extends AbstractType
{
    private DeliveryManRepository $deliveryManRepository;

    public function __construct(DeliveryManRepository $deliveryManRepository)
    {
        $this->deliveryManRepository = $deliveryManRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $deliveryMen = $this->deliveryManRepository->findAll();
        $choices = [];
        foreach ($deliveryMen as $dm) {
            $choices[$dm->getName()] = $dm->getDeliveryManId();
        }

        $builder
            ->add('make', TextType::class, [
                'label' => 'Make (Brand)',
                'required' => true
            ])
            ->add('model', TextType::class, [
                'label' => 'Model',
                'required' => true
            ])
            ->add('licensePlate', TextType::class, [
                'label' => 'License Plate',
                'required' => true
            ])
            ->add('vehicleType', ChoiceType::class, [
                'label' => 'Vehicle Type',
                'choices' => [
                    'Motorcycle' => 'motorcycle',
                    'Car' => 'car',
                    'Bicycle' => 'bicycle',
                    'Scooter' => 'scooter',
                    'Van' => 'van',
                    'Truck' => 'truck',
                    'Other' => 'other',
                ],
                'required' => true
            ])
            ->add('deliveryManId', ChoiceType::class, [
                'label' => 'Assigned Driver (Optional)',
                'choices' => $choices,
                'required' => false,
                'placeholder' => '-- Select a driver --',
                'empty_data' => null
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FleetCar::class,
        ]);
    }
}
