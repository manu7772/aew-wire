<?php
namespace Aequation\WireBundle\Security\interface;

// Symfony
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

interface WireAppVoterInterface extends VoterInterface
{

    public const ADD_ACTION_CLONE           = 'duplicate';

    public const ACTION_LIST                = 'action_list';
    public const ACTION_CREATE              = 'action_create';
    public const ACTION_CLONE               = 'action_clone';
    public const ACTION_READ                = 'action_read';
    public const ACTION_UPDATE              = 'action_update';
    public const ACTION_DELETE              = 'action_delete';
    public const ACTION_SENDMAIL            = 'action_sendmail';

    public const ADMIN_FW_ACTIONS           = 'admin';
    public const ADMIN_ACTION_LIST          = 'admin_action_list';
    public const ADMIN_ACTION_CREATE        = 'admin_action_create';
    public const ADMIN_ACTION_CLONE         = 'admin_action_clone';
    public const ADMIN_ACTION_READ          = 'admin_action_read';
    public const ADMIN_ACTION_UPDATE        = 'admin_action_update';
    public const ADMIN_ACTION_DELETE        = 'admin_action_delete';
    public const ADMIN_ACTION_SENDMAIL      = 'admin_action_sendmail';

    public const MAIN_FW_ACTIONS            = 'main';
    public const MAIN_ACTION_LIST           = 'main_action_list';
    public const MAIN_ACTION_CREATE         = 'main_action_create';
    public const MAIN_ACTION_CLONE          = 'main_action_clone';
    public const MAIN_ACTION_READ           = 'main_action_read';
    public const MAIN_ACTION_UPDATE         = 'main_action_update';
    public const MAIN_ACTION_DELETE         = 'main_action_delete';
    public const MAIN_ACTION_SENDMAIL       = 'main_action_sendmail';

    public static function getDefaultFirewall(): string;
    public static function getActions(string $firewall = null): array;
    public static function getFirewalls(): array;
    // Added actions
    public static function getAddedActions(): array;
    public static function getAddedActionsDescription(): array;

}
