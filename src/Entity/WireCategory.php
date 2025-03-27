<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Entity\interface\WireCategoryTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\Collection;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
// PHP
use Exception;

#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['name','type'], message: 'Cette catégorie {{ value }} existe déjà', groups: ['persist','update'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\TranslationEntity(class: WireCategoryTranslationInterface::class)]
abstract class WireCategory extends MappSuperClassEntity implements WireCategoryInterface
{

    use Unamed;

    public const ICON = [
        'ux' => 'tabler:clipboard-list',
        'fa' => 'fa-solid fa-clipboard-list'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire', groups: ['persist','update'])]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: 'Le type est obligatoire', groups: ['persist','update'])]
    protected ?string $type;
    protected array $typeChoices;

    #[ORM\Column(length: 64, nullable: true)]
    #[Assert\Length(max: 64, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères', groups: ['persist','update'])]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    #[ORM\OneToMany(targetEntity: WireCategoryTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    protected $translations;

    #[Gedmo\Translatable]
    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 128, unique: true)]
    protected $slug;


    public function __construct()
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->name;
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

    public function getTypeChoices(): array
    {
        return $this->typeChoices ??= $this->getEmbededStatus()->service->getCategoryTypeChoices(false, false, true);
    }

    public function getType(): ?string
    {
        return $this->type ?? null;
    }

    public function getTypeShortname(): string
    {
        return Objects::getShortname($this->type);
    }

    /**
     * Set type
     * 
     * @param string $type - classname or shortname
     * @return static
     * @throws Exception
     */
    public function setType(
        string $type
    ): static
    {
        $availables = $this->getAvailableTypes();
        if(!array_key_exists($type, $availables) && !in_array($type, $availables)) {
            throw new Exception(vsprintf('Error %s line %d: type "%s" not found!%sUse one of these types:%s', [__METHOD__, __LINE__, $type, PHP_EOL, PHP_EOL.'- '.join(PHP_EOL.'- ', $availables).join(PHP_EOL.'- ', array_keys($availables))]));
        }
        $this->type = $availables[$type] ?? $type;
        // dump($this->name.' ==> '.$this->type);
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
        foreach ($this->getTypeChoices() as $classname) {
            $types[Objects::getShortname($classname, false)] = $classname;
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