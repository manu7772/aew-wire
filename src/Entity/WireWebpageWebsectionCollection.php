<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WebsectionCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: '`webpage_sorted_websection`')]
#[HasLifecycleCallbacks]
class WireWebpageWebsectionCollection implements WebsectionCollectionInterface
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireWebpageInterface::class, inversedBy: 'sections')]
    #[Assert\NotNull(groups: ['persist','update'])]
    protected $webpage;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireWebsectionInterface::class)]
    #[Assert\NotNull(groups: ['persist','update'])]
    protected WireWebsectionInterface $websection;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Assert\NotNull(groups: ['persist','update'])]
    #[Gedmo\SortableGroup]
    protected string $sortgroup;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\SortablePosition]
    protected int $position = 0;


    public function __construct(
        WireWebpageInterface $webpage,
        WireWebsectionInterface $websection
    ) {
        $this->webpage = $webpage;
        $this->websection = $websection;
        $this->updateSortgroup();
    }


    public function getWebpage(): WireWebpageInterface
    {
        return $this->webpage;
    }

    public function getWebsection(): WireWebsectionInterface
    {
        return $this->websection;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function updateSortgroup(): static
    {
        $this->sortgroup = $this->webpage->getEuid().'_Websection';
        return $this;
    }

    public function getSortgroup(): string
    {
        return $this->sortgroup;
    }

    public function setSortgroup(string $sortgroup): static
    {
        $this->sortgroup = $sortgroup;
        return $this;
    }

}