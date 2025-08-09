<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Attribute\AdminGroup;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageTranslationInterface;
use Aequation\WireBundle\Entity\interface\WireTranslationInterface;
use Aequation\WireBundle\Entity\trait\Enabled;
use Aequation\WireBundle\Entity\trait\Prefered;
use Aequation\WireBundle\Entity\trait\Unamed;
use Aequation\WireBundle\Service\WireLanguageService;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
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
#[AdminGroup(group: 'Intl', order: 12, icon: 'tabler:flag-filled')]
abstract class WireLanguage extends MappSuperClassEntity implements WireLanguageInterface
{

    use Unamed, Enabled, Prefered;

    public const ICON = [
        'ux' => 'flag:@locale@-4x3',
        'ux-square' => 'flag:@locale@-1x1',
        'fa' => 'fa-flag'
    ];
    public const MAX_PREFERED = 1; // 1 is the maximum number of prefered sections in the database
    public const MIN_PREFERED = 1; // 1 is the minimum number of prefered sections in the database
    public const BY_PREFERED = [];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    protected ?int $id = null;

    #[ORM\Column(nullable: false, unique: true)]
    #[Assert\NotBlank(message: 'La locale est obligatoire', groups: ['persist','update'])]
    protected ?string $locale = null;
    // locale choices
    protected array $localeChoices;

    #[ORM\Column(nullable: false)]
    #[Assert\NotNull(message: 'Le fuseau horaire est obligatoire', groups: ['persist','update'])]
    protected ?string $timezone = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire', groups: ['persist','update'])]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Translatable]
    protected ?string $description = null;

    #[ORM\OneToMany(targetEntity: WireLanguageTranslationInterface::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    protected Collection $translations;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Gedmo\SortablePosition]
    protected int $position = 0;

    public function __construct()
    {
        parent::__construct();
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return empty($this->locale) ? parent::__toString() : $this->locale;
    }

    public function getMaxPrefered(): ?int
    {
        return static::MAX_PREFERED;
    }

    public function getMinPrefered(): ?int
    {
        return static::MIN_PREFERED;
    }

    public function getPreferedBy(): array
    {
        return static::BY_PREFERED;
    }

    public function getCountryIcon(
        string $type = 'ux'
    ): string
    {
        return preg_replace('/@locale@/', $this->getLocale(), static::ICON[$type]);
    }

    public static function getIcon(
        string $type = 'ux'
    ): string
    {
        switch ($type) {
            case 'ux':
            case 'ux-square':
                return 'tabler:flag-filled';
                break;
            default:
                return constant('static::ICON')[$type];
                break;
        }
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        if(!WireLanguageService::isValidLocale($locale)) {
            throw new \InvalidArgumentException(vsprintf('Error %s line %d:%sInvalid locale "%s"!%sPlease choose one of %s', [__METHOD__, __LINE__, PHP_EOL, $locale, PHP_EOL, implode(', ', WireLanguageService::getAvailableLocales())]));
        }
        $this->locale = $locale;
        $this->setTimezone(WireLanguageService::findTimezoneByLocale($locale));
        $currentLocale = $this->getEmbededStatus()->appWire->getLocale();
        $this->setName(WireLanguageService::getLocaleName($locale, $currentLocale));
        return $this;
    }

    public function getLocaleChoices(): array
    {
        return $this->localeChoices ??= $this->getEmbededStatus()->service->getLanguageLocaleChoices();
    }

    public function getDateTimezone(): ?DateTimeZone
    {
        return $this->timezone ? new DateTimeZone($this->timezone) : null;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        new DateTimeZone($this->timezone);
        return $this;
    }

    public function getName(): ?string
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

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

}