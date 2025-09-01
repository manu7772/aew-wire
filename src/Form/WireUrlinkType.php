<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\WireUrlink;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireUrlinkServiceInterface;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class WireUrlinkType extends WireAbstractType
{

    public const ENTITY_CLASS = WireUrlink::class;

    /** @var WireUrlinkServiceInterface */
    protected readonly WireEntityServiceInterface $entityService;

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
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                'priority' => -2
            ])
        ;

        $this->defaultListeners($builder);

    }

}