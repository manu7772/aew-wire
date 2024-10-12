<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitScreenableInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\trait\Screenable;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Repository\WireArticleRepository;
use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: WireArticleRepository::class)]
#[ClassCustomService(WireArticleServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[Slugable('name')]
abstract class WireArticle extends WireItem implements WireArticleInterface, TraitSlugInterface, TraitScreenableInterface
{

    use Slug, Screenable;

    public const ICON = "tabler:article";
    public const FA_ICON = "fa-regular fa-newspaper";


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column]
    protected array $content = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $end = null;


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(?\DateTimeInterface $start): static
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?\DateTimeInterface $end): static
    {
        $this->end = $end;
        return $this;
    }

}
