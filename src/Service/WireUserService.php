<?php

namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Repository\WireUserRepository;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;
// PHP
use Exception;

abstract class WireUserService extends RoleHierarchy implements WireUserServiceInterface
{

    USE TraitBaseService;
    use TraitBaseEntityService;

    public const ENTITY_CLASS = WireUser::class;
    public const EXCEPT_CHOICE_ROLES_EXPR = '/^((?!ROLE_)|ROLE_USER|ROLE_ALLOWED_TO_SWITCH)/';

    protected ?bool $darkmode = null;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected PaginatorInterface $paginator,
        public readonly ValidatorInterface $validator,
        // // public readonly NormalizerServiceInterface $normalizer,
        public readonly Security $security,
        public readonly AccessDecisionManagerInterface $accessDecisionManager,
        #[Autowire(param: 'security.role_hierarchy.roles')]
        protected array $subhierarchy
    )
    {
        parent::__construct($subhierarchy);
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEntityService->incDebugMode();
        $opresult ??= new Opresult();
        // Check all WireUserInterface entities
        $this->wireEntityService->decDebugMode();
        return $opresult;
    }

    public function getSecurity(): Security
    {
        return $this->appWire->security;
    }

    public function getUser(): ?WireUserInterface
    {
        return $this->getSecurity()->getUser();
    }

    public function createDefaultSuperAdmin(): WireUserInterface
    {
        $admin_email = $this->appWire->getParam('main_sadmin');
        if(empty($admin_email)) {
            throw new Exception(vsprintf('Error %s line %d: main_sadmin parameter not found!', [__METHOD__, __LINE__]));
        }
        $sadmin = $this->getRepository()->findOneBy(['email' => $admin_email]);
        if(!$sadmin){
            $data = [
                'email' => $admin_email,
                'name' => 'Dujardin',
                'firstname' => 'Emmanuel',
                'plainPassword' => 'sadmin',
                'darkmode' => true,
                'roles' => ['ROLE_SUPER_ADMIN'],
                'uname' => 'super_admin_manu',
            ];
            /** @var WireUserInterface */
            $sadmin = $this->createEntity($data);
            $sadmin->setSuperadmin();
            $this->saveUser($sadmin);
        }
        return $sadmin;
    }

    public function getMainAdminUser(
        bool $findSadminIfNotFound = false
    ): ?WireUserInterface {
        $admin_email = $this->appWire->getParam('main_admin');
        $user = $this->getRepository()->findOneBy(['email' => $admin_email]);
        return empty($user) && $findSadminIfNotFound
            ? $this->getMainSAdminUser()
            : $user;
    }

    public function getMainSAdminUser(): ?WireUserInterface
    {
        $sadmin_email = $this->appWire->getParam('main_sadmin');
        $sadmin = $this->getRepository()->findOneBy(['email' => $sadmin_email]);
        $sadmin ??= $this->createDefaultSuperAdmin();
        return $sadmin;
    }

    /**
     * Check if main SUPER ADMIN user (Webmaster) is still ROLE_SUPER_ADMIN
     * Check if enabled and verified, too
     * If not, restore ROLE_SUPER_ADMIN status and FLUSH changes in database
     * 
     * @return WireUserInterface|null
     */
    public function checkMainSuperadmin(): ?WireUserInterface
    {
        /** @var WireUserInterface&TraitEnabledInterface */
        $sadmin = $this->getMainSAdminUser();
        $sadmin ??= $this->createDefaultSuperAdmin();
        if($sadmin && !$sadmin->isValidSuperadmin()) {
            $sadmin->setSuperadmin();
            $this->saveUser($sadmin);
            return $sadmin;
        }
        return null;
    }

    public function loginUser(
        WireUserInterface|string $user
    ): ?Response
    {
        if(is_string($user)) {
            $user = $this->getRepository()->findOneBy(['email' => $user]);
        }
        return $user ? $this->getSecurity()->login($user, 'form_login', null) : null;
    }

    /**
     * Logout current User
     *
     * @param boolean $validateCsrfToken
     * @return Response|null
     */
    public function logoutCurrentUser(bool $validateCsrfToken = true): ?Response
    {
        return $this->getSecurity()->logout($validateCsrfToken);
    }

    /**
     * Update User last login
     *
     * @param WireUserInterface $user
     * @return static
     */
    public function updateUserLastLogin(
        WireUserInterface $user
    ): static {
        $user->updateLastLogin();
        $this->saveUser($user);
        return $this;
    }


    /**
     * Is User granted.
     *
     * @param [type] $attribute
     * @param [type] $subject
     * @return boolean
     */
    public function isGranted(
        $attribute, 
        $subject = null
    ): bool
    {
        return $this->security->isGranted($attribute, $subject);
    }

    /**
     * Is user granted for attributes
     * @see https://www.remipoignon.fr/symfony-comment-verifier-le-role-dun-utilisateur-en-respectant-la-hierarchie-des-roles/
     *
     * @param ?UserInterface $user
     * @param [type] $attributes
     * @param [type] $object
     * @param ?string $firewallName = null
     * @return boolean
     */
    public function isUserGranted(
        ?UserInterface $user,
        $attributes,
        $object = null,
        ?string $firewallName = null
    ): bool
    {
        $user ??= $this->getUser();
        if(empty($user)) {
            return $this->isGranted($attributes, $object);
        }
        $firewallName ??= $this->appWire->getFirewallName();
        $publics = $this->appWire->getPublicFirewalls();
        if(!in_array($firewallName, $publics)) {
            if($this->appWire->isDev()) {
                throw new Exception(vsprintf('Error %s line %d: could not determine user for firewall %s!', [__METHOD__, __LINE__, $firewallName]));
            }
            $firewallName = $this->appWire->getFirewallName();
        }
        $attributes = (array)$attributes;
        $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        return $this->accessDecisionManager->decide($token, $attributes, $object);
    }

    public function isRolesGranted(
        string|array $roles,
        $attributes,
        $object = null,
        ?string $firewallName = 'main'
    ): bool
    {
        /** @var UserInterface */
        $user = $this->createModel(['roles' => (array)$roles]);
        $result = $this->isUserGranted($user, $attributes, $object, $firewallName);
        unset($user);
        return $result;
    }


    /****************************************************************************************************
     * ROLE HIERARCHY
     */

        /**
     * Get roles map
     * 
     * UserService.php on line 222:
     * array:4 [▼
     *   "ROLE_COLLABORATOR" => array:1 [▼
     *       0 => "ROLE_USER"
     *   ]
     *   "ROLE_EDITOR" => array:2 [▼
     *       0 => "ROLE_COLLABORATOR"
     *       1 => "ROLE_USER"
     *   ]
     *   "ROLE_ADMIN" => array:3 [▼
     *       0 => "ROLE_EDITOR"
     *       1 => "ROLE_COLLABORATOR"
     *       2 => "ROLE_USER"
     *   ]
     *   "ROLE_SUPER_ADMIN" => array:5 [▼
     *       0 => "ROLE_ADMIN"
     *       1 => "ROLE_ALLOWED_TO_SWITCH"
     *       2 => "ROLE_EDITOR"
     *       3 => "ROLE_COLLABORATOR"
     *       4 => "ROLE_USER"
     *   ]
     * ]
     * 
     * @return array
     */
    public function getRolesMap(): array
    {
        return $this->map;
    }

    public function getAppRoles(
        bool $filter_main_roles = true
    ): array
    {
        return $filter_main_roles
            ? static::filterChoiceRoles(array_keys($this->map))
            : array_keys($this->map);
    }

    public static function filterChoiceRoles(
        array|WireUserInterface $roles
    ): array
    {
        if($roles instanceof WireUserInterface) {
            $roles = $roles->getRoles();
        }
        return array_filter($roles, fn($role) => !preg_match(static::EXCEPT_CHOICE_ROLES_EXPR, $role));
    }

    /**
     * Get reachable roles.
     *
     * @param array|WireUserInterface $roles
     * @param boolean $filter_main_roles
     * @return array
     */
    public function getAvailableRoles(
        string|array|WireUserInterface $roles,
        bool $filter_main_roles = true
    ): array
    {
        $roles = $roles instanceof WireUserInterface ? $roles->getRoles() : (array)$roles;
        return $filter_main_roles
            ? static::filterChoiceRoles($this->getReachableRoleNames($roles))
            : $this->getReachableRoleNames($roles);
    }

    /**
     * Get upper roles.
     *
     * @param string|array|WireUserInterface $roles
     * @param boolean $filter_main_roles
     * @return array
     */
    public function getUpperRoleNames(
        string|array|WireUserInterface $roles,
        bool $filter_main_roles = true
    ): array
    {
        $roles = $roles instanceof WireUserInterface ? $roles->getRoles() : (array)$roles;
        $upper_roles = array_unique(array_diff(array_keys($this->map), $this->getReachableRoleNames($roles)));
        return $filter_main_roles
            ? static::filterChoiceRoles($upper_roles)
            : $upper_roles;
    }

    public function compareUsers(
        WireUserInterface $manager,
        WireUserInterface $subordinate
    ): bool
    {
        throw new Exception(vsprintf('Error %s line %d: method %s not implemented!', [__METHOD__, __LINE__, __METHOD__]));
        // if(!in_array('ROLE_SUPER_ADMIN', $manager->getRoles())) {
        //     foreach ($subordinate->getRoles() as $role) {
        //         if(!$this->isUserGranted($manager, $role)) return false;
        //     }
        // }
        return true;
    }


    /****************************************************************************************************
     * QUERYS
     */

    public function saveUser(
        WireUserInterface $user
    ): static
    {

        if(empty($user->getId() && !$user->__estatus->isContained())) {
            $this->getEm()->persist($user);
        }
        $errors = $this->validator->validate($user);
        if(count($errors) > 0) {
            throw new Exception(vsprintf('Error %s line %d: %s', [__METHOD__, __LINE__, $errors]));
        }
        $this->em->flush();
        return $this;
    }

     public function getSuperadmins(): array
     {
         // return $this->getRepository()->findAll();
         return $this->getRepository()->findGranted('ROLE_ADMIN');
     }

     public function getAdmins(): array
     {
         // return $this->getRepository()->findAll();
         return $this->getRepository()->findGranted('ROLE_ADMIN');
     }


    /****************************************************************************************************/
    /** PAGINABLE                                                                                       */
    /****************************************************************************************************/

    /**
     * Get paginated context data.
     *
     * @param Request $request
     * @return array
     */
    public function getPaginatedContextData(
        ?Request $request = null
    ): array
    {
        $request ??= $this->appWire->getRequest();
        $fields =  [
            'id' => [
                'classes' => ['text-center','w-0'],
                'sortable' => true,
            ],
            'email' => [
                'sortable' => true,
            ],
            'name' => [
                'view_options' => [
                    'template' => ['from_string' => '{{ entity.name }}{% if entity.firstname is not null %}<span class="pl-2 italic text-sm font-extralight opacity-75"> {{ entity.firstname }}</span>{% endif %}']
                ],
                'sortable' => true,
            ],
            'ratings' => [
                'classes' => ['text-center'],
                'view_options' => [
                    'template' => ['from_string' => '{{ \'actions.count\'|trans({\'%count%\': entity.ratings|length}, \'Rating\') }}']
                ],
                // 'sortable' => false,
            ],
            'roles' => [
                'classes' => ['text-center'],
                'view_options' => [
                    'template' => ['from_string' => '{{ list_roles(entity.roles) }}']
                ],
                'sortable' => true,
            ],
        ];
        $model = $this->createModel();
        $entities = $this->getPaginated();
        return [
            'entities' => $entities,
            'fields' => $fields,
            'options' => [
                'alias' => WireUserRepository::ALIAS,
                'classname' => $model->getClassname(),
                'shortname' => $model->getShortname(),
                'trans_domain' => $model->getShortname(),
                'actions' => true,
            ],
        ];
    }


}
