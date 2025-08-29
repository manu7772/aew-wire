<?php
namespace Aequation\WireBundle\Form;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;

abstract class WireAbstractType extends AbstractType
{

    public const ENTITY_CLASS = BaseEntityInterface::class;

    protected readonly WireEntityServiceInterface $entityService;
    protected readonly string $classname;

    public function __construct(
        protected WireEntityManagerInterface $wireEm,
        protected TranslatorInterface $translator,
    )
    {
        $this->entityService = $this->wireEm->getEntityService(static::ENTITY_CLASS);
    }

    public function getFinalClassname(): string
    {
        return $this->classname ??= $this->entityService->getWireEm()->getEntitiesMetadata()->findOneFinal([static::ENTITY_CLASS])->getName();
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