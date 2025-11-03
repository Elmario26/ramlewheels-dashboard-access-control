<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Email Address',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('firstName', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'First Name',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Last Name',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200',
                    'placeholder' => '9123456789'
                ],
                'label' => 'Phone Number',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Administrator' => 'admin',
                    'Manager' => 'manager',
                    'Mechanic' => 'mechanic',
                    'Staff' => 'staff'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Role',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Suspended' => 'suspended'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200',
                    'rows' => 3,
                    'placeholder' => 'Additional notes about this user...'
                ],
                'label' => 'Notes',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ]
            ]);

        // Only add password field for new users or when explicitly requested
        if ($options['include_password']) {
            $builder->add('password', PasswordType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 focus:ring-2 focus:ring-[#B32224] focus:border-transparent hover:border-[#B32224] hover:bg-gray-600 transition-all duration-200'
                ],
                'label' => 'Password',
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-300 mb-2'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password' => false,
        ]);
    }
}
