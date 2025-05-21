<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('html_element_attributes')]
class HtmlElementAttributes
{

    public string $locale;
    public string $darkmodeClass;
    public string $switcherUrl;

    public function __construct(
        private AppWireServiceInterface $appWire,
    ) {
        $this->locale = $this->appWire->getLocale();
        $this->darkmodeClass = $this->appWire->getDarkmodeClass();
        $this->switcherUrl = 'aequation_wire_api.darkmode_switcher';
    }




}