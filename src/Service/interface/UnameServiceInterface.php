<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;

interface UnameServiceInterface extends WireEntityServiceInterface
{

    public function findOrphanUnames(): array;
    public function removeOrphanUnames(array|string|UnameInterface $unames): OpresultInterface;

}