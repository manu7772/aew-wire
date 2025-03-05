<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireEmailinkInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\WireEmailinkRepository;
use Aequation\WireBundle\Service\interface\WireEmailinkServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

abstract class WireEmailink extends WireRelink implements WireEmailinkInterface
{

    public const ICON = 'tabler:mail';
    public const FA_ICON = 'envolope';

    public const RELINK_TYPE = 'EMAIL';

    // #[Gedmo\SortableGroup]
    // protected WireItemInterface & TraitRelinkableInterface $itemowner;

    // #[Gedmo\SortablePosition]
    // protected int $position;


    public function setEmail(string $email): static
    {
        $this->mainlink = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->mainlink;
    }

}