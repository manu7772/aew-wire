<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\OnEventCall;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\SlugInterface;
use Aequation\WireBundle\Entity\interface\TraitCreatedInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\interface\UnamedInterface;
use Aequation\WireBundle\Entity\trait\Created;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Uid\UuidV7 as Uuid;
// PHP
use Exception;

#[MappedSuperclass]
#[UniqueEntity(fields: ['name','type'], message: 'Cette catégorie {{ value }} existe déjà pour ce type')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[HasLifecycleCallbacks]
#[Slugable(property: 'name')]
abstract class WireCategory extends MappSuperClassEntity implements WireCategoryInterface
{

    use Created, Slug, Unamed;

    public const ICON = "tabler:clipboard-list";
    public const FA_ICON = "fa-solid fa-clipboard-list";

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Serializer\Groups(['index'])]
    protected ?Uuid $id = null;

    #[ORM\Column(nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(updatable: false, nullable: false)]
    protected ?string $type;
    protected array $typeChoices;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $description = null;

    public function __construct()
    {
        parent::__construct();
        $this->type = static::DEFAULT_TYPE;
    }

    public function __toString(): string
    {
        return (string)$this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    #[OnEventCall(events: [FormEvents::PRE_SET_DATA])]
    public function setTypeChoices(
        WireCategoryServiceInterface $service
    ): static
    {
        $this->typeChoices = $service->getCategoryTypeChoices();
        return $this;
    }

    public function getTypeChoices(): array
    {
        return $this->typeChoices;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeShortname(): string
    {
        return Objects::getShortname($this->type);
    }

    public function setType(string $type): static
    {
        $availables = $this->getAvailableTypes();
        if(!array_key_exists($type, $availables)) {
            $memtype = $type;
            if(false === ($type = array_search($type, $availables))) {
                throw new Exception(vsprintf('Error %s line %d: type "%s" not found!', [__METHOD__, __LINE__, $memtype]));
            }
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Get list of available types
     * 
     * Returns array of classname => shortname
     * 
     * @return array
     */
    public function getAvailableTypes(): array
    {
        $types = [];
        foreach ($this->getTypeChoices() as $classname => $values) {
            $types[$classname] = Objects::getShortname($values, false);
        }
        return $types;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }


}