<?php
namespace Aequation\WireBundle\Security\Voter;

// Aequation

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Objects;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// Symfony
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class BaseEntityVoter extends Voter implements VoterInterface
{

    public const ENTITY_CLASS = WireEntityInterface::class;

    public function __construct(
        protected readonly AppWireServiceInterface $appWire
    ) {
    }

    public static function getEntityClassname(): ?string
    {
        if (!empty(static::ENTITY_CLASS) && is_a(static::ENTITY_CLASS, WireEntityInterface::class, true)) {
            return static::ENTITY_CLASS;
        }
        throw new Exception(vsprintf('Error %s line %d: the constant ENTITY_CLASS must be defined and instance of %s in %s', [__METHOD__, __LINE__, WireEntityInterface::class, static::class]));
    }

    protected function supports($attribute, $subject): bool
    {
        $classname = static::getEntityClassname();
        if(is_a($subject, $classname, true)) {
            return true;
        }
        $classnames = $this->getSupportedSubjectValues();
        return in_array($subject, $classnames);
    }

    public function getSupportedSubjectValues(): array
    {
        $classname = static::getEntityClassname();
        return [
            $classname,
            Objects::getShortname($classname, false),
            Objects::getShortname($classname, true),
        ];
    }

    public function voteOnAttribute(
        string $subject,
        mixed $attribute,
        TokenInterface $token
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