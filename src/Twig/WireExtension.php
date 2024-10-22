<?php
namespace Aequation\WireBundle\Twig;

// Symfony

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Strings;
use Aequation\WireBundle\Tools\Times;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
// PHP
use DateTimeImmutable;
use Twig\Extension\GlobalsInterface;
use Twig\Markup;

class WireExtension extends AbstractExtension implements GlobalsInterface
{

    public function __construct(
        private AppWireServiceInterface $appWire
    )
    {}

    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction('current_year', [Times::class, 'getCurrentYear']),
            // TURBO-UX
            new TwigFunction('turbo_memory', [$this, 'turboMemory']),
            new TwigFunction('turbo_preload', [$this, 'turboPreload']),
        ];

        if(!$this->appWire->isDev()) {
            // Prevent dump function call if not in dev evnironment
            $functions[] = new TwigFunction('dump', [$this, 'dump']);
        }

        return $functions;
    }

    /**
     * Get Twig globals
     * @return array
     */
    public function getGlobals(): array
    {
        return [
            'app' => $this->appWire,
            'currentYear' => $this->getCurrentYear(),
        ];
    }


    /*************************************************************************************
     * FUNCTIONS
     *************************************************************************************/

    /**
     * Get current year as YYYY
     * @return string
     */
    public function getCurrentYear(): string
    {
        $date = new DateTimeImmutable('NOW');
        return $date->format('Y');
    }

    /**
     * Enable/Disable data-turbo-temporary attribute
     * @param boolean $enable
     * @return Markup
     */
    public function turboMemory(bool $enable) : Markup
    {
        return Strings::markup(' data-turbo-temporary="'.($enable ? 'true' : 'false').'"');
    }

    /**
     * Enable/Disable data-turbo attribute
     * @param boolean $enable
     * @return Markup
     */
    public function turboPreload(bool $enable) : Markup
    {
        return Strings::markup(' data-turbo="'.($enable ? 'true' : 'false').'"');
    }

    /**
     * Removed dump() function to prevent error when production environment
     * @param mixed $value
     * @return null
     */
    public function dump(mixed $value): null
    {
        return null;
    }

}