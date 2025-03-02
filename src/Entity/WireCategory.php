<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Datetimed;
use Aequation\WireBundle\Entity\trait\Slug;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;

#[MappedSuperclass]
#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
#[UniqueEntity(fields: ['name','type'], message: 'Cette catégorie {{ value }} existe déjà')]
#[HasLifecycleCallbacks]
#[Gedmo\TranslationEntity(class: WireCategoryTranslationInterface::class)]
abstract class WireCategory extends MappSuperClassEntity implements WireCategoryInterface
{

    use Datetimed, Unamed;

    public const ICON = [
        'ux' => 'tabler:clipboard-list',
        'fa' => 'fa-solid fa-clipboard-list'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\Column(nullable: false)]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\Column(updatable: false, nullable: false)]
    protected ?string $type;
    protected array $typeChoices;

    #[ORM\Column(length: 64, nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    #[ORM\OneToMany(targetEntity: WireCategoryTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    private $translations;

    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 128, unique: true)]
    protected $slug;


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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(WireTranslationInterface $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

}