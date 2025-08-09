<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\BetweenSortedChildInterface;
use Aequation\WireBundle\Entity\interface\BetweenSortedParentInterface;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireArticleInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: '`sorted_relinks_article`')]
#[HasLifecycleCallbacks]
class WireArticleRelinkCollection extends WireBaseRelinkCollection
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireArticleInterface::class, inversedBy: 'relinks')]
    protected TraitRelinkableInterface&BetweenSortedParentInterface $parent;

    public function __construct(
        TraitRelinkableInterface&BetweenSortedParentInterface $parent,
        WireRelinkInterface&BetweenSortedChildInterface $relink
    ) {
        $this->parent = $parent;
        parent::__construct($relink);
    }

}