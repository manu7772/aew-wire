<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\TraitPreferedInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Service\WireLanguageService;
use DateTimeZone;
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
#[UniqueEntity(fields: ['locale'], message: 'Cette locale {{ value }} existe déjà', groups: ['persist','update'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\TranslationEntity(class: WireLanguageTranslationInterface::class)]
abstract class WireLanguage extends MappSuperClassEntity implements WireLanguageInterface
{

    use Unamed, Enabled, Prefered;

    public const ICON = [
        'ux' => 'tabler:flag',
        'fa' => 'fa-flag'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: 'La locale est obligatoire', groups: ['persist','update'])]
    protected string $locale;
    // locale choices
    protected array $localeChoices;

    #[ORM\Column(nullable: false)]
    #[Assert\NotNull()]
    protected string $timezone;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire', groups: ['persist','update'])]
    #[Gedmo\Translatable]
    protected string $name;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    #[ORM\OneToMany(targetEntity: WireLanguageTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    protected $translations;


    public function __construct()
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return empty($this->locale) ? parent::__toString() : $this->locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        if(!WireLanguageService::isValidLocale($locale)) {
            throw new Exception(vsprintf('Error %s line %d:%s- This locale %s is not valid!', [__METHOD__, __LINE__, PHP_EOL, $locale]));
        }
        $this->locale = $locale;
        $this->setTimezone(WireLanguageService::findTimezoneByLocale($locale));
        $currentLocale = $this->getEmbededStatus()->appWire->getCurrentLocale();
        $this->setName(WireLanguageService::getLocaleName($locale, $currentLocale));
        return $this;
    }

    public function getLocaleChoices(): array
    {
        return $this->localeChoices ??= $this->getEmbededStatus()->service->getLanguageLocaleChoices();
    }

    public function getDateTimezone(): ?DateTimeZone
    {
        return new DateTimeZone($this->timezone);
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
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