<?php
namespace Aequation\WireBundle\Form;

use RuntimeException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
// Symfony
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
// PHP
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;

abstract class WireAbstractType extends AbstractType
{

    public const ENTITY_CLASS = BaseEntityInterface::class;

    protected readonly WireClassMetadataInterface $wCmd;
    protected readonly WireEntityServiceInterface $entityService;

    public function __construct(
        protected WireEntityManagerInterface $wireEm,
        protected TranslatorInterface $translator,
    )
    {
        $wCmds = $this->wireEm->getEntitiesMetadata()->findFinals([static::ENTITY_CLASS]);
        if($wCmds->count() !== 1) {
            throw new RuntimeException(vsprintf('Error %s line %d: Unable to determine one unique entity for class "%s". Found %d entities.', [__METHOD__, __LINE__, static::ENTITY_CLASS, $wCmds->count()]));
        }
        $this->wCmd = $wCmds->first();
        $this->entityService = $this->wireEm->getEntityService($this->wCmd->getName());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->wCmd->getName(),
            'translation_domain' => $this->entityService->getEntityShortname(),
            'attr' => [
                'novalidate' => true,
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'registerType',
            ],
        ]);
    }

    protected function defaultListeners($builder): void
    {
        // Add default event listeners or subscribers here
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            if($form->getParent() && $form->has('submit')) {
                $form->remove('submit');
            }
        });
    }

}