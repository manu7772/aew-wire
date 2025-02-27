<?php
namespace Aequation\WireBundle\Twig;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
// use Twig\Extension\GlobalsInterface;
use Twig\Markup;
// PHP
use Exception;

class WireExtension extends AbstractExtension
{

    public function __construct(
        private AppWireServiceInterface $appWire
    )
    {}

    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction('current_year', [$this->appWire, 'getCurrentYear']),
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

    // /**
    //  * Get Twig globals
    //  * @return array
    //  */
    // public function getGlobals(): array
    // {
    //     return [
    //         'app' => $this->appWire,
    //         'currentYear' => $this->getCurrentYear(),
    //     ];
    // }


    /*************************************************************************************
     * FUNCTIONS
     *************************************************************************************/

    // /**
    //  * Get current year as YYYY
    //  * @return string
    //  */
    // public function getCurrentYear(): string
    // {
    //     $date = new DateTimeImmutable('NOW');
    //     return $date->format('Y');
    // }

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
        throw new Exception(vsprintf('Error %s line %d: function %s() is not available in production environment', [__METHOD__, __LINE__, 'dump']));
        return null;
    }

}