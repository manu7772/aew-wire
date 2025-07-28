<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'wire:hydra-items-show',
    template: '@AequationWire/components/hydra-items-show.html.twig'
)]
class HydraItemsShow
{


}