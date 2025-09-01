<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\WireImageInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;

trait Mainimage
{

    #[ORM\OneToOne(targetEntity: WireImageInterface::class, cascade: ['persist', 'remove'])]
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