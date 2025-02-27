<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\ExpressionLanguageServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use BadMethodCallException;
// Symfony
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\KernelInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(ExpressionLanguageServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class ExpressionLanguageService extends ExpressionLanguage implements ExpressionLanguageServiceInterface
{
    use TraitBaseService;

    public function __construct(
        // protected KernelInterface $kernel,
        protected ?CacheItemPoolInterface $cache = null,
        protected array $providers = []
    )
    {
        parent::__construct($cache, $providers);
    }

    public function addPhpFunctions(): static
    {
        $this->register('lowercase', function ($str): string {
            return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $str);
        }, function ($arguments, $str): string {
            return is_string($str) ? strtolower($str) : $str;
        });
        return $this;
    }

}