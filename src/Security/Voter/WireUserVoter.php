<?php
namespace Aequation\WireBundle\Security\Voter;

use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
// PHP
use Exception;

class WireUserVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireUser::class;

    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
        // if(!parent::voteOnAttribute($subject, $attribute, $token, $vote)) {
        //     return false;
        // }

        /** @var WireUserServiceInterface */
        $userService = $this->appWire->get(WireUserServiceInterface::class);
        // Context User
        $user = $userService->getUser();

        switch ($this->appWire->getFirewallName()) {
            case 'admin':
                // dump($subject.' ==> '.$this->appContext->getFirewallName());
                switch ($subject) {
                    case 'index':
                        return $userService->isGranted('ROLE_USER');
                        break;
                    case 'new':
                        return $userService->isGranted('ROLE_ADMIN');
                        break;
                    case 'show':
                        return $userService->isGranted('ROLE_USER');
                        break;
                    case 'edit':
                        return $attribute === $user || ($userService->compareUsers($user, $attribute) && $userService->isGranted('ROLE_COLLABORATOR'));
                        break;
                    case 'delete':
                        return $attribute === $user || ($userService->compareUsers($user, $attribute) && $userService->isGranted('ROLE_ADMIN'));
                        break;
                    default:
                        $vote->addReason(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        // throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
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
                        $vote->addReason(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        // throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        return false;
                        break;
                }
                break;
        }
        return false;
    }

}