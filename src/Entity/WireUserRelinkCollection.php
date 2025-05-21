<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\RelinkCollectionInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Entity\interface\WireRelinkInterface;
use Aequation\WireBundle\Entity\trait\BaseRelinkCollection;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sortable\Entity\Repository\SortableRepository;
// PHP
use Exception;

#[ORM\Entity(repositoryClass: SortableRepository::class)]
#[ORM\Table(name: '`user_sorted_relinks`')]
#[HasLifecycleCallbacks]
class WireUserRelinkCollection implements RelinkCollectionInterface
{
    use BaseRelinkCollection;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WireUserInterface::class, inversedBy: 'relinks')]
    protected $parent;

    public function __construct(
        WireUserInterface $parent,
        WireRelinkInterface $relink
    ) {
        $this->__construct_baserelinkcollection($parent, $relink);
    }

}