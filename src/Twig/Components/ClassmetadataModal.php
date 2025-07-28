<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'wire:classmetadata-modal',
    template: '@AequationWire/components/classmetadata-modal.html.twig'
)]
class ClassmetadataModal extends AbstractController
{

    use DefaultActionTrait;

    public WireClassMetadataInterface $wCmd;

}