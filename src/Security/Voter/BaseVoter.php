<?php
namespace Aequation\WireBundle\Security\Voter;

use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitPreferedInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Security\interface\WireAppVoterInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Tools\HttpRequest;
// Symfony
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// PHP
use Exception;
use ReflectionClass;

abstract class BaseVoter extends Voter implements WireAppVoterInterface
{

    public const INTERFACE = null;

    public readonly ?WireEntityServiceInterface $wireEntityService;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityManager
    )
    {
        $service = $this->wireEntityManager->getEntityService(static::getInterface());
        $this->wireEntityService = $service instanceof WireEntityServiceInterface ? $service : null;
    }

    public static function getInterface(): string
    {
        $interface = (string)static::INTERFACE;
        if(empty($interface) || !interface_exists($interface)) throw new Exception(vsprintf('Error %s line %d: INTERFACE %s is not defined or does not exist in %s!', [__METHOD__, __LINE__, json_encode($interface), static::class]));
        return $interface;
    }

    /**
     * Is valid $subject for this Voter
     * @param mixed $subject
     * @return boolean
     */
    protected function isValidSubject(mixed $subject): bool
    {
        $interface = static::getInterface();
        return is_object($subject)
            ? $subject instanceof $interface
            : is_a($subject, $interface, true);
    }

    private static function getConstants(
        ?callable $filter = null
    ): array
    {
        $rc = new ReflectionClass(static::class);
        return is_callable($filter)
            ? array_filter($rc->getConstants(), $filter, ARRAY_FILTER_USE_KEY)
            : $rc->getConstants();
    }

    /**
     * Get specific added actions for this Voter
     * @return array
     */
    public static function getAddedActions(): array
    {
        return static::getConstants(
            fn ($name) => preg_match('/^ADD_ACTION_/', $name)
        );
    }

    /**
     * Vote on attribute
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return boolean
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool
    {
        $vote = HttpRequest::isCli();
        /** @var WireUserInterface */
        $user = $token->getUser();
        if($this->isVoterNeeded($user)) {
            switch ($this->getFirewallByAction($attribute)) {
                case static::MAIN_FW_ACTIONS:
                    switch ($attribute) {
                        // --- MAIN/PUBLIC SIDE -----------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::MAIN_ACTION_LIST:
                            $vote = true;
                            break;
                        case static::ACTION_CREATE:
                        case static::MAIN_ACTION_CREATE:
                            $vote = true;
                            break;
                        case static::ACTION_READ:
                        case static::MAIN_ACTION_READ:
                            $vote = true;
                            break;
                        case static::ACTION_CLONE:
                        case static::MAIN_ACTION_CLONE:
                            $vote = true;
                            if($subject instanceof TraitEnabledInterface && $subject->isSoftdeleted()) $vote = false;
                            if(method_exists($subject, 'getName') && preg_match('/(\s-\scopie\d+)$/', $subject->getName())) $vote = false;
                            break;
                        case static::ACTION_UPDATE:
                        case static::MAIN_ACTION_UPDATE:
                            $vote = true;
                            break;
                        case static::ACTION_DELETE:
                        case static::MAIN_ACTION_DELETE:
                            $vote = true;
                            if($subject instanceof TraitOwnerInterface && $subject->getOwner() !== $user) $vote = false;
                            if($subject instanceof TraitPreferedInterface && $subject->isPrefered()) $vote = false;
                            break;
                    }
                    break;
                default:
                    # admin and others
                    switch ($attribute) {
                        // --- ADMIN SIDE -----------------------------------------------------------------------
                        case static::ACTION_LIST:
                        case static::ADMIN_ACTION_LIST:
                            $vote = $this->appWire->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_CREATE:
                        case static::ADMIN_ACTION_CREATE:
                            $vote = $this->appWire->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_READ:
                        case static::ADMIN_ACTION_READ:
                            $vote = $this->appWire->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_CLONE:
                        case static::ADMIN_ACTION_CLONE:
                            $vote = $this->appWire->isGranted('ROLE_COLLABORATOR');
                            if($subject instanceof TraitEnabledInterface && $subject->isSoftdeleted()) $vote = false;
                            if(method_exists($subject, 'getName') && preg_match('/(\s-\scopie\d+)$/', $subject->getName())) $vote = false;
                            break;
                        case static::ACTION_UPDATE:
                        case static::ADMIN_ACTION_UPDATE:
                            $vote = $this->appWire->isGranted('ROLE_COLLABORATOR');
                            break;
                        case static::ACTION_DELETE:
                        case static::ADMIN_ACTION_DELETE:
                            $vote = $this->appWire->isGranted('ROLE_COLLABORATOR');
                            if(!$this->appWire->isGranted('ROLE_ADMIN')) {
                                if($subject instanceof TraitOwnerInterface && $subject->getOwner() !== $user) $vote = false;
                            }
                            if($subject instanceof TraitPreferedInterface && $subject->isPrefered()) $vote = false;
                            break;
                    }
                    break;
            }
        }

        return $vote;
    }

    public static function getAddedActionsDescription(): array
    {
        $data = [];
        foreach (static::getAddedActions() as $const_name => $name) {
            $data[$const_name] = [
                'name' => $name,
                'action' => constant('static::'.strtoupper('ACTION_'.$name)),
                'action_main' => constant('static::'.strtoupper('MAIN_ACTION_'.$name)),
                'action_admin' => constant('static::'.strtoupper('ADMIN_ACTION_'.$name)),
            ];
        }
        return $data;
    }

    // protected function isGranted(
    //     mixed $attributes,
    //     mixed $subject = null,
    // ): bool
    // {
    //     return $this->appWire->isGranted($attributes, $subject);
    // }

    protected function isVoterNeeded(
        ?WireUserInterface $user = null,
    ): bool
    {
        if(HttpRequest::isCli()) return false;
        /** @var WireUserInterface|UserInterface */
        $user ??= $this->appWire->getUser();
        return $user?->isLoggable() ?: true;
    }

    // protected function getSubjectAsObject(
    //     mixed $subject,
    //     AppEntityManagerInterface $manager,
    // ): AppEntityInterface
    // {
    //     // if($subject instanceof EntityDto) $object = $subject->getFqcn();
    //     if(is_string($subject)) {
    //         if($manager->entityExists($subject, true, true)) {
    //             $object = $manager->getModel($subject);
    //         } else {
    //             throw new Exception(vsprintf('Error %s line %d: entity class "%s" does not exist or is not instantiable.', [__METHOD__, __LINE__, $subject]));
    //         }
    //     }
    //     return $object ?? $subject;
    // }

    /**
     * Get default Firewall
     * @return string
     */
    public static function getDefaultFirewall(): string
    {
        return static::ADMIN_FW_ACTIONS;
    }

    /**
     * Get Actions list
     * @param string|null $firewall
     * @return array
     */
    public static function getActions(
        ?string $firewall = null
    ): array
    {
        $test = empty($firewall) ? 'ACTION_' : strtoupper('('.$firewall.'_)?ACTION_');
        return static::getConstants(
            fn ($name) => preg_match('/^'.$test.'/', $name)
        );
    }

    /**
     * Get all Firewall names
     * @return array
     */
    public static function getFirewalls(): array
    {
        return static::getConstants(
            fn ($name) => preg_match('/_FW_ACTIONS$/', $name)
        );
    }

    /**
     * Get Firewall name of action
     * @param string $action
     * @return string
     */
    protected function getFirewallByAction(string $action): string
    {
        if(preg_match('/^action_/i', $action)) {
            // autodetermined action
            $firewall = $this->appWire->getFirewallName(false);
        } else {
            // action is in the $action name
            $test = explode('_action_', $action);
            $firewall = count($test) < 2
                ? $this->appWire->getFirewallName(false)
                : reset($test);
        }
        $firewalls = static::getFirewalls();
        return in_array($firewall, $firewalls) ? $firewall : static::getDefaultFirewall();
    }

    /**
     * Does this Voter support $subject
     * @param string $attribute
     * @param mixed $subject
     * @return boolean
     */
    protected function supports(
        string $attribute,
        mixed $subject
    ): bool
    {
        // if($subject instanceof EntityDto) $subject = $subject->getFqcn();
        $actions = $this->getActions($this->getFirewallByAction($attribute));
        return in_array($attribute, $actions, true) && $this->isValidSubject($subject);
    }

}