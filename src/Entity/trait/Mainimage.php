<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\WireImageInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait Mainimage
{

    #[ORM\OneToOne(targetEntity: WireImageInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    #[Assert\Valid(groups: ['persist','update'])]
    protected ?WireImageInterface $mainimage = null;

    public function getMainimage(): ?WireImageInterface
    {
        return $this->mainimage;
    }

    public function setMainimage(?WireImageInterface $mainimage): static
    {
        $this->mainimage = $mainimage;
        return $this;
    }

}