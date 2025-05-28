<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Entity\interface\TraitCategorizedInterface;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\trait\Categorized;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Webpageable;
use Aequation\WireBundle\Tools\Encoders;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], groups: ['registration','persist','update'], message: 'Cet email {{ value }} est déjà utilisé')]
#[ORM\HasLifecycleCallbacks]
#[SerializationMapping(WireUser::ITEMS_ACCEPT)]
abstract class WireUser extends WireItem implements WireUserInterface
{
    use Webpageable, Relinkable, Categorized;

    public const ICON = [
        'ux' => 'tabler:user-filled',
        'fa' => 'fa-user'
    ];
    public const ITEMS_ACCEPT = [
        'addresses' => [
            'field' => 'relinks',
            'require' => [WireAddresslinkInterface::class],
        ],
        'phones' => [
            'field' => 'relinks',
            'require' => [WirePhonelinkInterface::class],
        ],
        'emails' => [
            'field' => 'relinks',
            'require' => [WireEmailinkInterface::class],
        ],
        'urls' => [
            'field' => 'relinks',
            'require' => [WireUrlinkInterface::class],
        ],
    ];

    #[ORM\OneToMany(targetEntity: WireUserRelinkCollection::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(groups: ['persist','update'])]
    protected Collection $relinks;

    #[ORM\Column(length: 180)]
    #[Assert\Email(groups: ['registration','persist','update'], message: 'Cet email n\'est pas valide')]
    #[Assert\NotBlank(groups: ['registration','persist','update'], message: 'Cet email est obligatoire')]
    protected ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    protected ?string $password = null;

    /** 
     * @see https://symfony.com/doc/current/reference/constraints/PasswordStrength.html
     * @see https://github.com/symfony/symfony/blob/7.0/src/Symfony/Component/Validator/Constraints/PasswordStrength.php
     */
    // #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM, message: 'Ce mot de passe n\'est pas assez sécurisé')]
    // #[SecurityAssert\UserPassword(message: 'Votre mot de passe n\'est pas valable', groups: ['registration','persist'])]
    #[Assert\NotBlank(groups: ['registration','persist'], message: 'Le mot de passe est obligatoire')]
    protected ?string $plainPassword = null;

    #[ORM\Column(nullable: true)]
    protected ?string $firstname = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $functionality = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    #[ORM\Column]
    protected bool $darkmode = false;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    protected bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $lastLogin = null;

    /**
     * @var Collection<int, WireUserInterface>
     */
    #[ORM\ManyToMany(targetEntity: WireFactoryInterface::class, mappedBy: 'associates')]
    protected Collection $factorys;


    public function __toString(): string
    {
        return $this->getCivilName().' ['.$this->email.']';
    }

    public function isActive(): bool
    {
        return $this->isEnabled() && !$this->isExpired();
    }

    public function isLoggable(): bool
    {
        return $this->isActive()
            // && $this->isVerified()
            ;
    }

    public function isSadmin(): bool
    {
        return $this->HasRole(static::ROLE_SUPER_ADMIN);
    }

    public function isEqualTo(UserInterface $user): bool
    {
        /** @var WireUserInterface $user */
        return $user->getId() === $this->getId();
    }

    /**
     * Get print name
     * @return string
     */
    public function getCivilName(): string
    {
        $name = trim(str_replace(["\n", "\r"], '', $this->name.' '.$this->firstname));
        if(empty($name)) $name = $this->email;
        return (string)$name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get name for Address $name
     * @return string
     */
    public function getEmailName(): string
    {
        return $this->getCivilName();
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getLabel(): string
    {
        return $this->name.trim(' '.$this->firstname). ' ('.$this->email.')';
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        array_unshift($roles, static::ROLE_USER);
        return array_unique($roles);
    }

    /**
     * Set Roles
     * 
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = [];
        return $this->addRole($roles);
    }

    /**
     * Add Role
     *
     * @param string $role
     * @return static
     */
    public function addRole(string|array $role): static
    {
        $this->roles = array_unique(array_merge($this->roles, (array)$role));
        $this->checkRoles();
        return $this;
    }

    /**
     * Remove Role(s)
     *
     * @param string|array $role
     * @return static
     */
    public function removeRole(string|array $roles): static
    {
        $this->roles = array_diff($this->roles, (array)$roles);
        $this->checkRoles();
        return $this;
    }

    /**
     * Has Role
     *
     * @param string $role
     * @return boolean
     */
    public function HasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    #[ORM\PrePersist()]
    #[ORM\PreUpdate()]
    public function checkRoles(): static
    {
        $this->roles = array_diff($this->roles, [static::ROLE_USER]);
        $this->updateIsVerified();
        return $this;
    }


    /**
     * Set Superadmin
     *
     * @return static
     */
    public function setSuperadmin(): static
    {
        $this->addRole(static::ROLE_SUPER_ADMIN);
        $this->setEnabled(true);
        return $this;
    }

    public function isValidSuperadmin(): bool
    {
        return $this->HasRole(static::ROLE_SUPER_ADMIN)
            && $this->isLoggable();
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        if(!empty($this->id)) $this->updateUpdatedAt();
        return $this;
    }

    public function autoGeneratePassword(
        int $length = 32,
        ?string $chars = null,
        bool $replace = true
    ): static
    {
        if(empty($this->plainPassword) || $replace) $this->plainPassword = Encoders::generatePassword($length, $chars);
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function isDarkmode(): bool
    {
        return $this->darkmode;
    }

    public function setDarkmode(bool $darkmode): static
    {
        $this->darkmode = $darkmode;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt instanceof DateTimeImmutable
            ? $this->expiresAt < new DateTimeImmutable()
            : false;
    }

    public function expiresIn(): ?DateInterval
    {
        return $this->expiresAt instanceof DateTimeImmutable
            ? $this->expiresAt->diff(new DateTimeImmutable())
            : null;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getIsVerified(): bool
    {
        return $this->isVerified;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function updateIsVerified(): static
    {
        if(count($this->roles) > 0) {
            $this->setIsVerified(true);
        }
        return $this;
    }

    public function getLastLogin(): ?DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeImmutable $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function updateLastLogin(): static
    {
        $this->setLastLogin(new DateTimeImmutable('NOW'));
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getFunctionality(): ?string
    {
        return $this->functionality;
    }

    public function setFunctionality(?string $functionality = null): static
    {
        $this->functionality = $functionality;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = null): static
    {
        $this->description = $description;
        return $this;
    }

    public function getFactorys(): Collection
    {
        return $this->factorys;
    }

    public function addFactory(WireFactoryInterface $factory): static
    {
        if (!$this->factorys->contains($factory)) {
            $this->factorys->add($factory);
        }
        if(!$factory->hasAssociate($this)) {
            $factory->addAssociate($this);
        }
        return $this;
    }

    public function removeFactory(WireFactoryInterface $factory): static
    {
        $this->factorys->removeElement($factory);
        if($factory->hasAssociate($this)) {
            $factory->removeAssociate($this);
        }
        return $this;
    }

    public function hasFactory(WireFactoryInterface $factory): bool
    {
        return $this->factorys->contains($factory);
    }
}
