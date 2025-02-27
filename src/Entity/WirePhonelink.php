<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WirePhonelinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\WirePhonelinkRepository;
use Aequation\WireBundle\Service\interface\WirePhonelinkServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: WirePhonelinkRepository::class)]
#[ClassCustomService(WirePhonelinkServiceInterface::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[Slugable('name')]
class WirePhonelink extends WireRelink implements WirePhonelinkInterface
{

    public const ICON = 'tabler:phone';
    public const FA_ICON = 'phone';

    public const RELINK_TYPE = 'PHONE';

    #[Gedmo\SortableGroup]
    protected TraitRelinkableInterface $itemowner;

    #[Gedmo\SortablePosition]
    protected int $position;

    
    public function setPhone(string $phone): static
    {
        $this->mainlink = preg_replace('/[^0-9\+]/', '', $phone);
        return $this;
    }

    public function getPhone(): string
    {
        return $this->mainlink;
    }

}