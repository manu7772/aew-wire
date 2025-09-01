<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WirePhonelink;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class WirePhonelinkType extends WireAbstractType
{

    public const ENTITY_CLASS = WirePhonelink::class;

    /** @var WirePhonelinkServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => false,
                'translation_domain' => $this->entityService->getEntityShortname(),
                // 'help' => 'help.name',
            ])
            ->add('phone', TextType::class, [
                'label' => 'fields.phone',
                'attr' => [
                    'type' => 'tel',
                ],
                'required' => true,
                'translation_domain' => $this->entityService->getEntityShortname(),
                'constraints' => [
                    new NotNull(message: 'errors.phone_required'),
                    new NotBlank(message: 'errors.phone_required'),
                    new Regex(
                        pattern: '/^\+?[0-9\s]+/',
                        message: 'errors.phone_invalid_format',
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