<?php

namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Component\PaginatedContextData;
use Aequation\WireBundle\Dto\interface\WireEntityDtoInterface;
use Aequation\WireBundle\Dto\WireUserDto;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\WireUser;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Repository\BaseWireRepository;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Knp\Component\Pager\PaginatorInterface;
// PHP
use Exception;
use Traversable;

#[AsAlias(WireUserServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class WireUserService extends RoleHierarchy implements WireUserServiceInterface
{

    USE TraitBaseService;
    use TraitBaseEntityService;

    public const ENTITY_CLASS = WireUser::class;
    public const EXCEPT_CHOICE_ROLES_EXPR = '/^((?!ROLE_)|ROLE_USER|ROLE_ALLOWED_TO_SWITCH)/';
    public const DEFAULT_CHECK_DB_OPTIONS = [
        'flush_one_by_one' => false,
        'load_all_if_less_or_equal_than' => 100, // If the number of entities is less or equal than this value, all entities will be loaded in one query
    ];

    protected ?bool $csstheme = null;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
        public readonly Security $security,
        public readonly AccessDecisionManagerInterface $accessDecisionManager,
        #[Autowire(param: 'security.role_hierarchy.roles')]
        protected array $subhierarchy
    )
    {
        parent::__construct($subhierarchy);
    }


    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function getUser(): ?WireUserInterface
    {
        return $this->security->getUser();
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
                'uname' => 'super_admin_manu',
            ];
            /** @var WireUserInterface */
            $sadmin = $this->wireEm->createEntity(static::ENTITY_CLASS);
            $sadmin->setEmail($data['email']);
            $sadmin->setName($data['name']);
            $sadmin->setFirstname($data['firstname']);
            $sadmin->setPlainPassword($data['plainPassword']);
            $sadmin->setUname($data['uname']);
            $sadmin->setSuperadmin();
            $this->saveUser($sadmin);
            $this->appWire->addFlash('success', vsprintf('Super Admin user "%s" created.<br>Super Admin can connect now.', [$admin_email]));
        }
        return $sadmin;
    }

    public function getMainAdminUser(
        bool $findSadminIfNotFound = false
    ): ?WireUserInterface {
        $admin_email = $this->appWire->getParam('main_admin');
        $admin = $this->getRepository()->findOneBy(['email' => $admin_email]);
        if($findSadminIfNotFound) {
            $admin ??= $this->getMainSAdminUser(true);
        }
        return $admin;
    }

    public function getMainSAdminUser(
        bool $createIfNotFound = false
    ): ?WireUserInterface
    {
        $sadmin_email = $this->appWire->getParam('main_sadmin');
        $sadmin = $this->getRepository()->findOneBy(['email' => $sadmin_email]);
        if($createIfNotFound) {
            $sadmin ??= $this->createDefaultSuperAdmin();
        }
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
        $sadmin = $this->getMainSAdminUser(true);
        if($sadmin && !$sadmin->isSuperadmin()) {
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
        return $user ? $this->security->login($user, 'form_login', null) : null;
    }

    /**
     * Logout current User
     *
     * @param boolean $validateCsrfToken
     * @return Response|null
     */
    public function logoutCurrentUser(bool $validateCsrfToken = true): ?Response
    {
        return $this->security->logout($validateCsrfToken);
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
     * Is user granted for attributes, and optionally for object and firewall.
     * @see https://www.remipoignon.fr/symfony-comment-verifier-le-role-dun-utilisateur-en-respectant-la-hierarchie-des-roles/
     *
     * @param ?UserInterface $user
     * @param [type] $attributes
     * @param [type] $object
     * @param ?string $firewallName = null
     * @return boolean
     */
    public function isGrantedForUser(
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
        $attributes = (array)$attributes;
        $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        return $this->accessDecisionManager->decide($token, $attributes, $object);
        // #################################
        // ---> new version does not work!!!
        // #################################
        // $user ??= $this->getUser();
        // if(empty($firewallName)) {
        //     return empty($user)
        //         ? $this->security->isGranted($attributes, $object)
        //         : $this->security->isGrantedForUser($user, $attributes, $object);
        // }
        // $token = new UsernamePasswordToken(
        //     $user,
        //     $firewallName ?? $this->appWire->getFirewallName(),
        //     $user->getRoles()
        // );
        // return $this->accessDecisionManager->decide($token, (array) $attributes, $object);
    }

    public function isRolesGranted(
        string|array $roles,
        $attributes,
        $object = null,
        ?string $firewallName = 'main'
    ): bool
    {
        /** @var UserInterface */
        $user = $this->wireEm->createModel(static::ENTITY_CLASS, ['roles' => (array)$roles]);
        $result = $this->isGrantedForUser($user, $attributes, $object, $firewallName);
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

    public function getUpperRole(array $roles): ?string
    {
        $upper = reset($roles);
        foreach($roles as $role) {
            if(count($this->map[$role] ?? []) > count($this->map[$upper] ?? [])) {
                $upper = $role;
            }
        }
        return $upper;
    }

    public function compareUsers(
        WireUserInterface $manager,
        WireUserInterface $subordinate
    ): bool
    {
        // throw new Exception(vsprintf('Error %s line %d: method %s not implemented!', [__METHOD__, __LINE__, __METHOD__]));
        // if(!in_array('ROLE_SUPER_ADMIN', $manager->getRoles())) {
            foreach ($subordinate->getRoles() as $role) {
                if(!$this->isGrantedForUser($manager, $role)) return false;
            }
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
        $this->wireEm->validateEntity($user, throws: true);
        if($user->getSelfState()->isNew()) {
            $this->getEm()->persist($user);
        }
        $this->getEm()->flush();
        return $this;
    }

     public function getSuperadmins(): array
     {
         return $this->getRepository()->findGranted('ROLE_ADMIN');
     }

     public function getAdmins(): array
     {
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
    public function getPaginatedContextData(array $options = []): PaginatedContextDataInterface
    {
        $options = [
            'fields' => [
                'id' => [
                    'classes' => ['w-1'],
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
                // 'ratings' => [
                //     'classes' => ['text-center'],
                //     'view_options' => [
                //         'template' => ['from_string' => '{{ \'actions.count\'|trans({\'%count%\': entity.ratings|length}, \'Rating\') }}']
                //     ],
                //     // 'sortable' => false,
                // ],
                'roles' => [
                    // 'classes' => ['text-center'],
                    'view_options' => [
                        'template' => ['from_string' => '{{ list_roles(entity.roles) }}']
                    ],
                    'sortable' => true,
                ],
            ],
        ];
        return new PaginatedContextData($this, $options);
    }

}
