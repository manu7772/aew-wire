<?php
namespace Aequation\WireBundle\Form;

use Symfony\Component\Form\AbstractType;
use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class WirePhonelinkType extends AbstractType
{

    public const ENTITY_CLASS = WirePhonelink::class;

    public readonly string $classname;

    public function __construct(
        private TranslatorInterface $translator,
        private WirePhonelinkServiceInterface $entityService
    )
    {}

    public function getFinalClassname(): string
    {
        return $this->classname ??= $this->entityService->getWireEm()->getEntitiesMetadata()->findOneFinal([static::ENTITY_CLASS])->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phone', TextType::class, [
                'label' => 'fields.phone',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Le numéro de téléphone est obligatoire'),
                    new Regex(
                        pattern: '/^\+?[0-9\s]+/',
                        message: 'Le numéro de téléphone doit contenir uniquement des chiffres (et le caractère + en début, optionellement)',
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
                'novalidate' => true,
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'registerType',
            ]
        ]);
    }

}