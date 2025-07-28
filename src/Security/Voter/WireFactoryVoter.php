<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireFactory;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;

class WireFactoryVoter extends WireItemVoter
{

    public const ENTITY_CLASS = WireFactory::class;

}