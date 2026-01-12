<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireSlide;
use Aequation\WireBundle\Service\interface\WireSlideServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class WireSlideType extends WireAbstractType
{

    public const ENTITY_CLASS = WireSlide::class;

    /** @var WireSlideServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireSlide */
        // $slide = $builder->getData();
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
                            'jpg' => ['slide/jpeg'],
                            'jpeg' => ['slide/jpeg'],
                            'png' => ['slide/png'],
                            'gif' => ['slide/gif'],
                            'webp' => ['slide/webp'],
                        ],
                        extensionsMessage: 'Format d\'slide invalide ({{ extensions }}).',
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
