<?php
namespace Aequation\WireBundle\Form;

use Symfony\Component\Form\AbstractType;
use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Service\interface\WireUrlinkServiceInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class WireUrlinkType extends AbstractType
{

    public const ENTITY_CLASS = WireUrlink::class;

    public readonly string $classname;

    public function __construct(
        private TranslatorInterface $translator,
        private WireUrlinkServiceInterface $entityService
    )
    {}

    public function getFinalClassname(): string
    {
        return $this->classname ??= $this->entityService->getWireEm()->getEntitiesMetadata()->findOneFinal([static::ENTITY_CLASS])->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'fields.url',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Le champ URL est obligatoire'),
                    new Regex(
                        pattern: '/^\+?[0-9\s]+/',
                        message: 'Le champ URL est invalide',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->getFinalClassname(),
            'translation_domain' => $this->entityService->getEntityShortname(),
            'attr' => [
                // 'novalidate' => true,
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'registerType',
            ],
        ]);
    }

}