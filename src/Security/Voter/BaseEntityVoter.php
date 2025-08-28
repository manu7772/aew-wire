<?php
namespace Aequation\WireBundle\Security\Voter;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
// PHP
use Exception;

abstract class BaseEntityVoter extends Voter implements VoterInterface
{

    public const ENTITY_CLASS = BaseEntityInterface::class;

    public readonly string $entityClass;

    public function __construct(
        protected readonly AppWireServiceInterface $appWire,
        protected readonly WireEntityManagerInterface $wireEm,
    ) {
        $this->entityClass = $this->getEntityClassname();
    }

    public static function getEntityClassname(): string
    {
        if (!empty(static::ENTITY_CLASS) && is_a(static::ENTITY_CLASS, BaseEntityInterface::class, true)) {
            return static::ENTITY_CLASS;
        }
        throw new Exception(vsprintf('Error %s line %d: the constant ENTITY_CLASS must be defined and instance of %s in %s', [__METHOD__, __LINE__, BaseEntityInterface::class, static::class]));
    }

    protected function supports($attribute, $subject): bool
    {
        if(preg_match('/^[A-Z_]+$/', $attribute)) {
            // Reject attributes that are all uppercase (usually ROLE_*)
            return false;
        }
        if($subject instanceof $this->entityClass || is_a($subject, $this->entityClass, true)) {
            return true;
        }
        // includes all sub-shortnames
        if($subject_classname = $this->wireEm->getEntitiesMetadata()->findEntityClassname($subject)) {
            return is_a($subject_classname, $this->entityClass, true);
        }
        return false;
    }


    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
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
                        return $this->appWire->isGranted('ROLE_COLLABORATOR');
                        break;
                    case 'delete':
                        return $this->appWire->isGranted('ROLE_COLLABORATOR');
                        break;
                    default:
                        $vote->addReason(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
                        // throw new Exception(vprintf('Error %s line %d: Unknown subject %s', [__METHOD__, __LINE__, $subject]));
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
                        return !($attribute instanceof TraitEnabledInterface) || $attribute->isActive();
                        break;
                    case 'edit':
                        return false;
                        break;
                    case 'delete':
                        return false;
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