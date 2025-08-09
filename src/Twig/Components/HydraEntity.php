<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Component\interface\HydraItemInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
// use Aequation\WireBundle\Component\WireClassMetadata;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\Form\FormInterface;
// use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
// use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\Component\Validator\ConstraintViolationList;

#[AsTwigComponent(
    name: 'wire:hydra-entity',
    template: '@AequationWire/components/hydra-entity.html.twig'
)]
class HydraEntity extends AbstractController
{

    // use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[ExposeInTemplate(name: 'entity', getter: 'getEntity')]
    public ?object $entity = null;
    #[ExposeInTemplate(name: 'errors', getter: 'getErrors')]
    public array|ConstraintViolationList $errors = [];
    #[ExposeInTemplate(name: 'hydraItem', getter: 'getHydraItem')]
    public ?HydraItemInterface $hydraItem = null;
    public ?WireClassMetadataInterface $wCmd;

    public function __construct(
        public readonly WireEntityManagerInterface $wireEm
    ) {
    } 


    // public function instantiateForm(): FormInterface
    // {
    //     $form = $this->createFormBuilder();
    //     return $form->getForm();
    // }

    public function getEntity(): ?object
    {
        return $this->entity ?? $this->getHydraItem()?->getPersistedOrNew() ?? null;
    }

    public function getErrors(): array|ConstraintViolationList
    {
        return $this->errors ?? [];
    }

    public function getHydraItem(): ?HydraItemInterface
    {
        return $this->hydraItem ?? null;
    }

    public function getWCmd(): ?WireClassMetadataInterface
    {
        return $this->wCmd ??= ($entity = $this->getEntity()) ? $this->wireEm->getEntityMetadata($entity) : null;
    }

}