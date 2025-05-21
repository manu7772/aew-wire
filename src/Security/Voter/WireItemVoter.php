<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation
use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;

abstract class WireItemVoter extends BaseEntityVoter
{

    public const ENTITY_CLASS = WireItem::class;


    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token
    ): bool
    {
        if(!parent::voteOnAttribute($subject, $attribute, $token)) {
            return false;
        }

        // $categoryService = $this->appWire->get(WireItemServiceInterface::class);

        switch ($this->appWire->getFirewallName()) {
            case 'admin':
                // dump($subject.' ==> '.$this->appContext->getFirewallName());
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
                // dump($subject.' ==> '.$this->appContext->getFirewallName());
                // Default is public
                switch ($subject) {
                    case 'index':
                        return true;
                        break;
                    case 'new':
                        return $this->appWire->isGranted('ROLE_USER');
                        break;
                    case 'show':
                        return $attribute->isActive();
                        break;
                    case 'edit':
                        return $attribute->getOwner() === $this->appWire->getUser();
                        break;
                    case 'delete':
                        return $attribute->getOwner() === $this->appWire->getUser();
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