<?php
namespace Aequation\WireBundle\Entity\interface;

use Doctrine\Common\Collections\Collection;

interface WireMenuInterface extends WireEcollectionInterface, TraitPreferedInterface, TraitWebpageableInterface
{
    public function getWebpages(bool $filterActives = false): Collection;
    public function getSubmenus(bool $filterActives = false): Collection;
}