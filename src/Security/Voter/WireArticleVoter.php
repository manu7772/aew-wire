<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireArticle;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;

class WireArticleVoter extends WireItemVoter
{

    public const ENTITY_CLASS = WireArticle::class;

}