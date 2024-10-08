<?php
namespace Aequation\WireBundle\Security\Voter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DevVoter extends Voter
{

    public function __construct(
        private Security $security,
        #[Autowire('%env(APP_ENV)%')]
        private string $env,
    ) {}

    public function supports(
        string $attribute,
        mixed $subject
    ): bool
    {
        $attributes = explode('|', $attribute);
        return count($attributes) > 1 || in_array($this->env, $attributes);
    }

    public function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool
    {
        $attributes = explode('|', $attribute);
        foreach ($attributes as $attr) {
            if($this->env === $attr || $this->security->isGranted($attr)) return true;
        }
        return false;
    }

}