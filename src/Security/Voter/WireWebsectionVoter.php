<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireWebsection;
use Aequation\WireBundle\Service\interface\WireWebsectionServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;

abstract class WireWebsectionVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireWebsection::class;


    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token
    ): bool
    {
        if(!parent::voteOnAttribute($subject, $attribute, $token)) {
            return false;
        }

        // $categoryService = $this->appWire->get(WireWebsectionServiceInterface::class);

        switch ($this->appWire->getFirewallName()) {
            case 'admin':
                switch ($subject) {
                    case 'index':
                        return $this->appWire->isGranted('ROLE_COLLABORATOR');
                        break;
                    case 'new':
                        return $this->appWire->isGranted('ROLE_COLLABORATOR');
                        break;
                    case 'show':
                        return $this->appWire->isGranted('ROLE_COLLABORATOR');
                        break;
                    case 'edit':
                        return $this->appWire->isGranted('ROLE_ADMIN') || $attribute->getOwner() === $this->appWire->getUser();
                        break;
                    case 'delete':
                        return $this->appWire->isGranted('ROLE_ADMIN') || $attribute->getOwner() === $this->appWire->getUser();
                        break;
                    default:
                        throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        return false;
                        break;
                }
                break;
            default:
                // Default is public
                switch ($subject) {
                    case 'index':
                        return false;
                        break;
                    case 'new':
                        return false;
                        break;
                    case 'show':
                        return true;
                        break;
                    case 'edit':
                        return false;
                        break;
                    case 'delete':
                        return false;
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