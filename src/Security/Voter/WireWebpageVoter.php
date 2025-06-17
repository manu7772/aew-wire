<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireWebpage;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;

abstract class WireWebpageVoter extends WireItemVoter
{

    public const ENTITY_CLASS = WireWebpage::class;

}