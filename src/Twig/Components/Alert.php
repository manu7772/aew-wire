<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(
    name: 'wire:alert',
    template: '@AequationWire/components/alert.html.twig'
)]
class Alert
{

    #[ExposeInTemplate(name: 'icon')]
    public bool $icon = true;
    #[ExposeInTemplate(name: 'type')]
    public string $type = 'info';

}