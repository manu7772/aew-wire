<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitScreenableInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WireHtmlcodeInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Entity\trait\Screenable;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Repository\WireHtmlcodeRepository;
use Aequation\WireBundle\Service\interface\WireHtmlcodeServiceInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: WireHtmlcodeRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ClassCustomService(WireHtmlcodeServiceInterface::class)]
#[Slugable('name')]
abstract class WireHtmlcode extends WireEcollection implements WireHtmlcodeInterface
{

    use Slug, Screenable;

    public const ICON = "tabler:code";
    public const FA_ICON = "fa-solid fa-code";
    public const CODE_HTML_TYPES = [
        WireWebpageInterface::class,
        WireWebsectionInterface::class,
    ];

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

    public function setContent(string|array $content): static
    {
        $this->content = (array)$content;
        return $this;
    }


}
