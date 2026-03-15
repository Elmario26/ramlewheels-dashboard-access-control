<?php

namespace App\Form;

use App\Entity\Sales;
use App\Entity\Cars;
use App\Entity\Customer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SalesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicle', EntityType::class, [
                'class' => Cars::class,
                'choice_label' => function(Cars $car) {
                    return $car->getBrand() . ' ' . $car->getYear() . ' - ₱' . number_format($car->getPrice(), 2);
                },
                'placeholder' => 'Select a vehicle',
                'attr' => [
                    'class' => 'form-control',
                    'data-controller' => 'vehicle-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a vehicle'])
                ]
            ])
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function(Customer $customer) {
                    return $customer->getFullName() . ' (' . $customer->getEmail() . ')';
                },
                'placeholder' => 'Select a customer',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'data-controller' => 'customer-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please select a customer'])
                ]
            ])
            ->add('salePrice', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'data-controller' => 'price-input'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Sale price is required']),
                    new Assert\Positive(['message' => 'Sale price must be positive'])
                ]
            ])
            ->add('downPayment', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'data-controller' => 'payment-input'
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Down payment must be positive or zero'])
                ]
            ])
            ->add('financingAmount', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'data-controller' => 'financing-input'
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Financing amount must be positive or zero'])
                ]
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => [
                    'Cash' => 'cash',
                    'Bank Transfer' => 'bank_transfer',
                    'Check' => 'check',
                    'Credit Card' => 'credit_card',
                    'Financing' => 'financing',
                    'Other' => 'other'
                ],
                'required' => false,
                'placeholder' => 'Select payment method',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('saleDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'data' => new \DateTime()
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Completed' => 'completed',
                    'Pending' => 'pending',
                    'Cancelled' => 'cancelled'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Additional notes about the sale...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sales::class,
        ]);
    }
}
