<?php
namespace Aequation\WireBundle\Entity;

// Aequation

use Aequation\WireBundle\Entity\interface\ResetPasswordRequestInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Repository\ResetPasswordRequestRepository;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
// PHP
use DateTimeInterface;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: '`reset_pwd_request`')]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
class ResetPasswordRequest extends BaseMappSuperClassEntity implements ResetPasswordRequestInterface
{
    use ResetPasswordRequestTrait;

    public const ICON = [
        'ux' => 'tabler:lock-down',
        'fa' => 'fa-lock'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    protected ?WireUserInterface $user = null;

    public function __construct(WireUserInterface $user, DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->user = $user;
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): WireUserInterface
    {
        return $this->user;
    }
}
