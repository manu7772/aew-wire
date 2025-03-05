<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireCategory;

abstract class WireCategoryVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireCategory::class;

}