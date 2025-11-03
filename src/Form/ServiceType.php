<?php

namespace App\Form;

use App\Entity\Service;
use App\Entity\Customer;
use App\Entity\Cars;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function(Customer $customer) {
                    return $customer->getFullName() . ' (' . $customer->getPhone() . ')';
                },
                'placeholder' => 'Select a customer',
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Customer',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('vehicle', EntityType::class, [
                'class' => Cars::class,
                'choice_label' => function(Cars $car) {
                    return $car->getBrand() . ' ' . $car->getYear() . ' (' . $car->getPlateNumber() . ')';
                },
                'placeholder' => 'Select a vehicle (optional)',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Vehicle',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('serviceType', ChoiceType::class, [
                'choices' => [
                    'Oil Change' => 'oil_change',
                    'Brake Service' => 'brake_service',
                    'Engine Repair' => 'engine_repair',
                    'Transmission Service' => 'transmission_service',
                    'Tire Service' => 'tire_service',
                    'Electrical Repair' => 'electrical_repair',
                    'Body Work' => 'body_work',
                    'Paint Job' => 'paint_job',
                    'Interior Repair' => 'interior_repair',
                    'AC Service' => 'ac_service',
                    'General Maintenance' => 'general_maintenance',
                    'Other' => 'other'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Service Type',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200',
                    'rows' => 3,
                    'placeholder' => 'Describe the service details...'
                ],
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('cost', MoneyType::class, [
                'currency' => 'PHP',
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200',
                    'placeholder' => '0.00'
                ],
                'label' => 'Service Cost',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'In Progress' => 'in_progress',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('serviceDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Service Date',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('completionDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Completion Date',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('assignedMechanic', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'placeholder' => 'Select a mechanic (optional)',
                'required' => false,
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->andWhere('u.status = :status')
                        ->setParameter('role', 'mechanic')
                        ->setParameter('status', 'active')
                        ->orderBy('u.firstName', 'ASC');
                },
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Assigned Mechanic',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200',
                    'rows' => 3,
                    'placeholder' => 'Additional notes...'
                ],
                'label' => 'Notes',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
