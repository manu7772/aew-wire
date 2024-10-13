<?php
namespace Aequation\WireBundle\Twig;

// Symfony

use Aequation\WireBundle\Tools\Times;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
// PHP
use DateTimeImmutable;

class WireExtension extends AbstractExtension
{

    public function __construct(
        private KernelInterface $kernel
    )
    {}

    public function getFunctions(): array
    {
        $functions = [
            new TwigFunction('current_year', [Times::class, 'getCurrentYear']),
        ];

        if($this->kernel->getEnvironment() !== 'dev') {
            // Prevent dump function call if not in dev evnironment
            $functions[] = new TwigFunction('dump', [$this, 'dump']);
        }

        return $functions;
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
     * Removed dump() function to prevent error when production environment
     * @param mixed $value
     * @return null
     */
    public function dump(mixed $value): null
    {
        return null;
    }

}