<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Vehicle Documents' => 'Vehicle Documents',
                    'Customer Documents' => 'Customer Documents',
                    'Internal Dealership Files' => 'Internal Dealership Files',
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-[#B32224] focus:ring-[#B32224] sm:text-sm',
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-700 mb-2'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a category',
                    ]),
                ],
            ])
            ->add('documentType', ChoiceType::class, [
                'label' => 'Document Type',
                'choices' => [
                    // Vehicle Documents
                    'OR/CR' => 'OR/CR',
                    'Deed of Sale' => 'Deed of Sale',
                    'Emission Test' => 'Emission Test',
                    'Vehicle Registration' => 'Vehicle Registration',
                    'Warranty Document' => 'Warranty Document',
                    // Customer Documents
                    'ID Document' => 'ID Document',
                    'Agreement' => 'Agreement',
                    'Receipt' => 'Receipt',
                    'Invoice' => 'Invoice',
                    'Contract' => 'Contract',
                    // Internal Dealership Files
                    'Form' => 'Form',
                    'Template' => 'Template',
                    'Permit' => 'Permit',
                    'Service Record' => 'Service Record',
                    'Sales Contract' => 'Sales Contract',
                    'Other' => 'Other',
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-[#B32224] focus:ring-[#B32224] sm:text-sm',
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-700 mb-2'
                ],
            ])
            ->add('file', FileType::class, [
                'mapped' => false,
                'required' => !$options['is_edit'],
                'label' => 'Document File',
                'attr' => [
                    'class' => 'block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none',
                    'accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.xls,.xlsx',
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-700 mb-2'
                ],
                'constraints' => array_filter([
                    !$options['is_edit'] ? new NotBlank([
                        'message' => 'Please select a file to upload.',
                    ]) : null,
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid document file (PDF, Word, Excel, or Image)',
                    ])
                ]),
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-[#B32224] focus:ring-[#B32224] sm:text-sm',
                    'rows' => 3,
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-700 mb-2'
                ],
            ])
            ->add('relatedEntityType', ChoiceType::class, [
                'required' => false,
                'label' => 'Related To',
                'choices' => [
                    'Vehicle' => 'Cars',
                    'Sale' => 'Sales',
                    'Service' => 'Service',
                    'Customer' => 'Customer',
                    'None' => null,
                ],
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-[#B32224] focus:ring-[#B32224] sm:text-sm',
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-700 mb-2'
                ],
            ])
            ->add('relatedEntityId', TextType::class, [
                'required' => false,
                'label' => 'Related Entity ID',
                'attr' => [
                    'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-[#B32224] focus:ring-[#B32224] sm:text-sm',
                    'placeholder' => 'Enter ID (optional)',
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-medium text-gray-700 mb-2'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'is_edit' => false,
        ]);
    }
}

