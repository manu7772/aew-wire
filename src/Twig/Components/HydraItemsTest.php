<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(
    name: 'wire:hydra-items-test',
    template: '@AequationWire/components/hydra-items-test.html.twig'
)]
class HydraItemsTest
{

    #[ExposeInTemplate(name: 'item')]
    public array $item = [];

}