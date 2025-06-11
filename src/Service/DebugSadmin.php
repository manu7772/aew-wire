<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Attribute\DebugToOptimize;
use Aequation\WireBundle\Service\interface\DebugSadminInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(DebugSadminInterface::class, public: false)]
#[Autoconfigure(autowire: true, lazy: true)]
class DebugSadmin implements DebugSadminInterface
{
    use TraitBaseService;

    public function getToOptimize(): array
    {
        $listOfClasses = '#^Aequation\\\#';
        Objects::filterDeclaredClasses($listOfClasses, true);
        $opts = [];
        /** @var array $listOfClasses */
        foreach($listOfClasses as $class) {
            $opts = array_merge($opts, Objects::getMethodAttributes($class, DebugToOptimize::class));
        }
        return $opts;
    }

}