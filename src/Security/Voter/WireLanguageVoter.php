<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireLanguage;

class WireLanguageVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireLanguage::class;

}