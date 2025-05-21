<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Entity\WireItem;
use Doctrine\Common\Collections\Collection;

// Symfony

interface WireEcollectionInterface extends WireItemInterface, BetweenManyParentInterface
{

}