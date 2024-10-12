<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitScreenableInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\trait\Screenable;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Repository\WireFactoryRepository;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: WireFactoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ClassCustomService(WireFactoryServiceInterface::class)]
#[Slugable('name')]
abstract class WireFactory extends WireItem implements WireFactoryInterface, TraitSlugInterface, TraitScreenableInterface
{

    use Slug, Screenable;

    public const ICON = "tabler:building-factory-2";
    public const FA_ICON = "fa-solid fa-industry";

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column]
    protected array $content = [];

    
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

}
