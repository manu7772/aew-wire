<?php
namespace Aequation\WireBundle\Service\interface;


interface DebugSadminInterface
{
    /**
     * Returns an array of methods to optimize.
     *
     * @return array
     */
    public function getToOptimize(): array;
}