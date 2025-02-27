<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireFactoryInterface;
use Aequation\WireBundle\Entity\trait\Relinkable;
use Aequation\WireBundle\Entity\trait\Webpageable;
use Aequation\WireBundle\Repository\WireFactoryRepository;
use Aequation\WireBundle\Service\interface\WireFactoryServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: WireFactoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ClassCustomService(WireFactoryServiceInterface::class)]
#[Slugable('name')]
abstract class WireFactory extends WireItem implements WireFactoryInterface, TraitRelinkableInterface
{

    use Webpageable, Relinkable;

    public const ICON = [
        'ux' => 'tabler:building-factory-2',
        'fa' => 'fa-solid fa-industry'
    ];


}
