<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireSlide;
use Aequation\WireBundle\Service\interface\WireSlideServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class WireSlideVoter extends WireItemVoter
{

    public const ENTITY_CLASS = WireSlide::class;

    // public function voteOnAttribute(
    //     string $subject,
    //     mixed $attribute,
    //     TokenInterface $token,
    //     ?Vote $vote = null
    // ): bool
    // {
    //     if(!parent::voteOnAttribute($subject, $attribute, $token, $vote)) {
    //         return false;
    //     }

    //     // $categoryService = $this->appWire->get(WireSlideServiceInterface::class);

    //     switch ($this->appWire->getFirewallName()) {
    //         case 'admin':
    //             switch ($subject) {
    //                 case 'index':
    //                     return $this->appWire->isGranted('ROLE_COLLABORATOR');
    //                     break;
    //                 case 'new':
    //                     return $this->appWire->isGranted('ROLE_COLLABORATOR');
    //                     break;
    //                 case 'show':
    //                     return $this->appWire->isGranted('ROLE_COLLABORATOR');
    //                     break;
    //                 case 'edit':
    //                     return $this->appWire->isGranted('ROLE_ADMIN') || $attribute->getOwner() === $this->appWire->getUser();
    //                     break;
    //                 case 'delete':
    //                     return $this->appWire->isGranted('ROLE_ADMIN') || $attribute->getOwner() === $this->appWire->getUser();
    //                     break;
    //                 default:
    //                     throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
    //                     return false;
    //                     break;
    //             }
    //             break;
    //         default:
    //             // Default is public
    //             switch ($subject) {
    //                 case 'index':
    //                     return false;
    //                     break;
    //                 case 'new':
    //                     return false;
    //                     break;
    //                 case 'show':
    //                     return true;
    //                     break;
    //                 case 'edit':
    //                     return false;
    //                     break;
    //                 case 'delete':
    //                     return false;
    //                     break;
    //                 default:
    //                     throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
    //                     return false;
    //                     break;
    //             }
    //             break;
    //     }
    //     return false;
    // }

}