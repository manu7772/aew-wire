<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Repository\ResetPasswordRequestRepository;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
// PHP
use DateTimeInterface;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: '`reset_pwd_request`')]
class ResetPasswordRequest extends MappSuperClassEntity implements ResetPasswordRequestInterface, WireEntityInterface
{
    use ResetPasswordRequestTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected $id = null;

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
