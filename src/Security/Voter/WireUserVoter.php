<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;

abstract class WireUserVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireUser::class;

    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token
    ): bool
    {
        if(!parent::voteOnAttribute($subject, $attribute, $token)) {
            return false;
        }

        $userService = $this->appWire->get(WireUserServiceInterface::class);
        // Context User
        $user = $userService->getUser();

        switch ($this->appWire->getFirewallName()) {
            case 'admin':
                // dump($subject.' ==> '.$this->appContext->getFirewallName());
                switch ($subject) {
                    case 'index':
                        return $userService->isUserGranted($user, 'ROLE_USER');
                        break;
                    case 'new':
                        return $userService->isUserGranted($user, 'ROLE_ADMIN');
                        break;
                    case 'show':
                        return $userService->isUserGranted($user, 'ROLE_USER');
                        break;
                    case 'edit':
                        return $attribute === $user || ($userService->compareUsers($user, $attribute) && $userService->isUserGranted($user, 'ROLE_COLLABORATOR'));
                        break;
                    case 'delete':
                        return $attribute === $user || ($userService->compareUsers($user, $attribute) && $userService->isUserGranted($user, 'ROLE_ADMIN'));
                        break;
                    default:
                        throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        return false;
                        break;
                }
                break;
            default:
                // dump($subject.' ==> '.$this->appContext->getFirewallName());
                // Default is public
                switch ($subject) {
                    case 'index':
                        return false;
                        break;
                    case 'new':
                        return empty($user);
                        break;
                    case 'show':
                        return $attribute === $user;
                        break;
                    case 'edit':
                        return $attribute === $user;
                        break;
                    case 'delete':
                        return $attribute === $user;
                        break;
                    default:
                        throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        return false;
                        break;
                }
                break;
        }

        return false;
    }

}