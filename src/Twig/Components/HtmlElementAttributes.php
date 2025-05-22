<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Symfony\Component\Routing\Router;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class HtmlElementAttributes
{

    public string $locale;
    public string $darkmodeClass;
    public string $switcherUrl;

    public function __construct(
        AppWireServiceInterface $appWire
    ) {
        $this->locale = $appWire->getLocale();
        $this->darkmodeClass = $appWire->getDarkmodeClass();
        $this->switcherUrl = $appWire->get('router')->generate('aequation_wire_api.darkmode_switcher');
    }




}