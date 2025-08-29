<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\WireMenu;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireMenuServiceInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
// Symfony
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WireMenuType extends AbstractType
{

    public const ENTITY_CLASS = WireMenu::class;

    public readonly string $classname;

    public function __construct(
        private TranslatorInterface $translator,
        private WireMenuServiceInterface $entityService
    )
    {}

    public function getFinalClassname(): string
    {
        return $this->classname ??= $this->entityService->getWireEm()->getEntitiesMetadata()->findOneFinal([static::ENTITY_CLASS])->getName();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WireMenu */
        // $menu = $builder->getData();
        $builder
            ->add('name', null, [
                'label' => 'fields.name',
                'required' => true,
                'priority' => 25
            ])
            ->add('title', null, [
                'label' => 'fields.title',
                'required' => true,
                'priority' => 25
            ])
            ->add('linktitle', null, [
                'label' => 'fields.linktitle',
                'required' => true,
                'priority' => 25
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'actions.save',
                // 'attr' => ['data-submit-actions' => 'save_index'],
                'priority' => -1
            ])
        ;

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
            ]
        ]);
    }
}
