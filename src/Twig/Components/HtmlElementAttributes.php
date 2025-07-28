<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Symfony\Component\Routing\Router;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'wire:html-element-attributes',
    template: '@AequationWire/components/html-element-attributes.html.twig'
)]
class HtmlElementAttributes
{

    public string $locale;
    public string $csstheme;
    public string $switcherUrl;

    public function __construct(
        AppWireServiceInterface $appWire
    ) {
        $this->locale = $appWire->getLocale();
        $this->csstheme = $appWire->getCsstheme();
        $this->switcherUrl = $appWire->get('router')->generate('aequation_wire_api.csstheme_define');
    }




}