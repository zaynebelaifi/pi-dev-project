<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Form for the "Reset Password" step.
 * Uses a repeated password field to confirm the new password.
 */
class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type'            => PasswordType::class,
            'first_options'   => [
                'label' => 'New password',
                'attr'  => [
                    'placeholder'  => 'At least 8 characters',
                    'autocomplete' => 'new-password',
                    'class'        => 'form-input',
                ],
            ],
            'second_options'  => [
                'label' => 'Confirm new password',
                'attr'  => [
                    'placeholder'  => 'Repeat your new password',
                    'autocomplete' => 'new-password',
                    'class'        => 'form-input',
                ],
            ],
            'invalid_message' => 'The password fields must match.',
            'mapped'          => false,   // we handle hashing manually in the controller
            'constraints'     => [
                new NotBlank(['message' => 'Please enter a new password.']),
                new Length([
                    'min'        => 8,
                    'minMessage' => 'Your password must be at least {{ limit }} characters.',
                    'max'        => 4096,   // prevent bcrypt DoS
                ]),
                new Regex([
                    'pattern' => '/[A-Z]/',
                    'message' => 'Your password must contain at least one uppercase letter.',
                ]),
                new Regex([
                    'pattern' => '/[0-9]/',
                    'message' => 'Your password must contain at least one number.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
