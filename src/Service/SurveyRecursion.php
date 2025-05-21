<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\SurveyRecursionInterface;
// PHP
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(SurveyRecursionInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class SurveyRecursion implements SurveyRecursionInterface
{
    protected array $__src = [];

    public function __construct(
        private readonly AppWireServiceInterface $appWire
    ) {
    }

    public function isActive(): bool
    {
        return !$this->appWire->isProd();
    }

    
    /**
     * Survey recursion in some methods (DEV only)
     * use: $this->survey(__METHOD__.'::*somename*');
     * 
     * @param string $name
     * @param int|null $max
     * @return void
     */
    public function survey(
        string $name,
        ?int $max = null,
        ?string $errorMessage = null
    ): void {
        if ($this->isActive()) {
            $max ??= self::MAX_SURVEY_RECURSION;
            $this->__src[$name] ??= 0;
            $this->__src[$name]++;
            if ($this->__src[$name] >= $max) {
                $errorMessage ??= vsprintf('Error %s line %d: "%s" recursion limit %d reached!', [__METHOD__, __LINE__, $name, $max]);
                throw new Exception($errorMessage);
            }
        }
    }

}