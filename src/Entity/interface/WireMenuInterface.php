<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Interface\MenuShapeInterface;
use Doctrine\Common\Collections\Collection;

interface WireMenuInterface extends WireEcollectionInterface, TraitPreferedInterface, TraitWebpageableInterface, MenuShapeInterface
{
    public function getWebpages(bool $filterActives = false): Collection;
    public function getSubmenus(bool $filterActives = false): Collection;
}