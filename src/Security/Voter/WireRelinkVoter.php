<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireRelink;

abstract class WireRelinkVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireRelink::class;

}