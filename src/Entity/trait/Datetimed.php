<?php
namespace Aequation\WireBundle\Entity\trait;

use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
use Aequation\WireBundle\Entity\WireLanguage;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Persistence\Event\LifecycleEventArgs;
// PHP
use DateTimeImmutable;
use DateTimeZone;
use Exception;

trait Datetimed
{
    #[ORM\ManyToOne(targetEntity: WireLanguageInterface::class, fetch: 'EAGER')]
    #[Assert\NotNull()]
    protected WireLanguageInterface $langage;
    // language choices
    protected array $languageChoices;

    #[ORM\Column(updatable: false, nullable: false)]
    #[Assert\NotNull()]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $updatedAt = null;

    public function __construct_datetimed(): void
    {
        $this->updateCreatedAt();
        $this->setTimezone(AppWireServiceInterface::DEFAULT_TIMEZONE);
        if(!($this instanceof TraitDatetimedInterface)) throw new Exception(vsprintf('Error %s line %d: this class %s should implement %s!', [__METHOD__, __LINE__, static::class, TraitDatetimedInterface::class]));

    }

    public function getLastActionAt(): DateTimeImmutable
    {
        return $this->updatedAt ?? $this->createdAt;
    }

    /**
     * Returns true if last action on this entity is before the given $date
     * @param DateTimeImmutable|string $date
     * @return boolean
     */
    public function compareLastAction(
        DateTimeImmutable|string $date,
    ): bool
    {
        if(is_string($date)) $date = new DateTimeImmutable($date);
        $compar = $this->getLastActionAt();
        return empty($compar) || $date > $compar;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateUpdatedAt(LifecycleEventArgs $args = null): static
    {
        $this->setUpdatedAt();
        return $this;
    }

    public function setUpdatedAt(
        ?DateTimeImmutable $updatedAt = null
    ): static
    {
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function updateCreatedAt(LifecycleEventArgs $args = null): static
    {
        $this->setCreatedAt();
        return $this;
    }

    public function setCreatedAt(
        ?DateTimeImmutable $createdAt = null
    ): static
    {
        if(empty($this->createdAt)) {
            $this->createdAt = $createdAt ?? new DateTimeImmutable();
        }
        return $this;
    }

    public function getLanguage(): WireLanguageInterface
    {
        return $this->langage;
    }

    public function setLanguage(WireLanguageInterface $langage): static
    {
        $this->langage = $langage;
        return $this;
    }

    public function getLanguageChoices(): array
    {
        return $this->languageChoices ??= $this->getEmbededStatus()->wireEntityManager->getEntityService(WireLanguage::class)->getLanguageChoices();
    }

    public function getLocale(): ?string
    {
        return $this->langage->getLocale();
    }

    public function getDateTimezone(): ?DateTimeZone
    {
        return $this->langage->getDateTimezone();
    }

    public function getTimezone(): string
    {
        return $this->langage->getTimezone();
    }


}