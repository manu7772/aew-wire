<?php

namespace Aequation\WireBundle\Twig\Components;

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

#[AsTwigComponent(
    name: 'wire:table-entity',
    template: '@AequationWire/components/table-entity.html.twig'
)]
class TableEntity extends AbstractController
{

    // use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[ExposeInTemplate(name: 'entity', getter: 'getEntity')]
    public object $entity;
    public WireClassMetadataInterface $wCmd;

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
        return $this->entity ?? null;
    }

    public function getWCmd(): WireClassMetadataInterface
    {
        return $this->wCmd ??= $this->wireEm->getEntityMetadata($this->entity);
    }

}