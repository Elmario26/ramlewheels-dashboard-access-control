<?php

namespace App\Form;

use App\Entity\Cars;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CarsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter brand name'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('year', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter year'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('mileage', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter mileage'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('conditions', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter condition'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('price', NumberType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter price'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('make', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter make/model'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('color', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter color'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('plateNumber', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter plate number'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('engineNumber', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter engine number'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('damageDescription', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#B32224] sm:text-sm sm:leading-6',
                    'placeholder' => 'Enter damage description (optional)',
                    'rows' => 3
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900'
                ]
            ])
            ->add('images', FileType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none',
                    'accept' => 'image/*',
                    'multiple' => 'multiple',
                ],
                'label' => 'Vehicle Images',
                'label_attr' => [
                    'class' => 'block text-sm font-medium leading-6 text-gray-900 mb-2'
                ],
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '10M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/jpg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp',
                                ],
                                'mimeTypesMessage' => 'Please upload a valid image file',
                            ])
                        ]
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cars::class,
        ]);
    }
}