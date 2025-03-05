<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\ItemCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: '`item_ecollection`')]
class ItemCollection implements ItemCollectionInterface
{

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireEcollectionInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'ecollection_id', referencedColumnName: 'id', nullable: false, unique: true)]
    #[Gedmo\SortableGroup]
    private WireEcollectionInterface $ecollection;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireItemInterface::class, inversedBy: 'parents')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private WireItemInterface $item;

    #[Gedmo\SortablePosition]
    #[ORM\Column(type: Types::INTEGER)]
    private $position = 0;

    public function __construct(
        WireEcollectionInterface $ecollection,
        WireItemInterface $item
    )
    {
        $this->ecollection = $ecollection;
        $this->item = $item;
    }

    public function getEcollection(): WireEcollectionInterface
    {
        return $this->ecollection;
    }

    // public function setEcollection(WireEcollectionInterface $ecollection): static
    // {
    //     $this->ecollection = $ecollection;
    //     return $this;
    // }

    public function getItem(): WireItemInterface
    {
        return $this->item;
    }

    // public function setItem(WireItemInterface $item): static
    // {
    //     $this->item = $item;
    //     return $this;
    // }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

}