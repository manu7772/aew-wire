<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireImage;
use Aequation\WireBundle\Service\interface\WireImageServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class WireImageType extends WireAbstractType
{

    public const ENTITY_CLASS = WireImage::class;

    /** @var WireImageServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireImage */
        // $image = $builder->getData();
        $builder
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => false,
                'priority' => 25
            ])
            ->add('description', TextareaType::class, [
                'label' => 'fields.description',
                'required' => false,
                'priority' => 1
            ])
            ->add('file', FileType::class, [
                'label' => 'fields.file',
                'required' => true,
                'multiple' => false,
                'priority' => 2,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est de {{ limit }} {{ suffix }}.',
                        extensions: [
                            'jpg' => ['image/jpeg'],
                            'jpeg' => ['image/jpeg'],
                            'png' => ['image/png'],
                            'gif' => ['image/gif'],
                            'webp' => ['image/webp'],
                        ],
                        extensionsMessage: 'Format d\'image invalide ({{ extensions }}).',
                        groups: ['persist','update'],
                    ),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'priority' => -2
            ])
        ;

        $this->defaultListeners($builder);

    }

}
