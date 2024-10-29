<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\TraitCreatedInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\TraitScreenableInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\trait\Created;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Screenable;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Tools\Encoders;
use DateInterval;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
// PHP
use DateTimeImmutable;

#[MappedSuperclass()]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
abstract class WireUser extends MappSuperClassEntity implements WireUserInterface
{
    use Enabled, Created, Unamed, Screenable;

    public const ICON = "tabler:user-filled";
    public const FA_ICON = "user";
    public const SERIALIZATION_PROPS = ['id','email'];

    #[ORM\Column(length: 180)]
    protected ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
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
    #[SecurityAssert\UserPassword(message: 'Votre mot de passe n\'est pas valable', groups: ['registration'])]
    // #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM, message: 'Ce mot de passe n\'est pas assez sécurisé')]
    protected ?string $plainPassword = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $lastname = null;

    #[ORM\Column]
    protected bool $darkmode = true;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    protected bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $lastLogin = null;


    public function __toString(): string
    {
        return $this->getCivilName().' ['.$this->email.']';
    }

    public function isLoggable(): bool
    {
        return $this->isEnabled() && $this->isVerified() && !($this->isSoftdeleted() || $this->isExpired());
    }

    public function isEqualTo(UserInterface $user): bool
    {
        /** @var WireUserInterface $user */
        return
            $user->getEmail() === $this->getEmail()
            && $user->getId() === $this->getId()
            ;
    }

    /**
     * Get print name
     * @return string
     */
    public function getCivilName(): string
    {
        $name = trim(str_replace(["\n", "\r"], '', $this->firstname.' '.$this->lastname));
        if(empty($name)) $name = $this->email;
        return $name;
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

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = static::ROLE_USER;
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
        foreach ($roles as $role) {
            $this->addRole($role);
        }
        return $this;
    }

    /**
     * Add Role
     *
     * @param string $role
     * @return static
     */
    public function addRole(string $role): static
    {
        if(!$this->HasRole($role)) $this->roles[] = $role;
        // $this->roles = array_unique($this->roles);
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

    /**
     * Set Superadmin
     *
     * @return static
     */
    public function setSuperadmin(): static
    {
        $this->addRole(static::ROLE_SUPER_ADMIN);
        $this->setEnabled(true);
        $this->setSoftdeleted(false);
        $this->setIsVerified(true);
        return $this;
    }

    public function isValidSuperadmin(): bool
    {
        return $this->HasRole(static::ROLE_SUPER_ADMIN)
            && $this->isEnabled()
            && !$this->isSoftdeleted()
            && $this->isVerified();
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
        $this->updateUpdatedAt();
        return $this;
    }

    public function autoGeneratePassword(
        int $length = 32,
        string $chars = null,
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;
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
}
