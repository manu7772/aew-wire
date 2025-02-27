<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireEmailink;
use Aequation\WireBundle\Service\interface\WireEmailinkServiceInterface;

class WireEmailinkService extends WireRelinkService implements WireEmailinkServiceInterface
{

    const ENTITY_CLASS = WireEmailink::class;

}