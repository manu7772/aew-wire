<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\WireAddresslinkRepository;
use Aequation\WireBundle\Service\interface\WireAddresslinkServiceInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: WireAddresslinkRepository::class)]
#[ClassCustomService(WireAddresslinkServiceInterface::class)]
#[ORM\HasLifecycleCallbacks]
class WireAddresslink extends WireRelink implements WireAddresslinkInterface
{

    public const ICON = 'tabler:map-pin';
    public const FA_ICON = 'link';

    public const RELINK_TYPE = 'ADDRESS';

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Serializer\Groups(['index'])]
    protected ?string $ville = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Serializer\Groups(['index'])]
    protected ?string $codePostal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Serializer\Groups(['index'])]
    protected ?string $url = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Serializer\Groups(['index'])]
    protected ?array $gps = null;

    #[Gedmo\SortableGroup]
    protected WireItemInterface & TraitRelinkableInterface $itemowner;

    #[Gedmo\SortablePosition]
    protected int $position;


    public function getAddressLines(bool $joinCPandVille = true): array
    {
        $lines = [];
        if($this->getAddress()) {
            $lines[] = $this->getAddress();
        }
        if($joinCPandVille) {
            $lines[] = trim($this->getCodePostal().' '.$this->getVille());
        } else {
            if($this->getCodePostal()) {
                $lines[] = $this->getCodePostal();
            }
            if($this->getVille()) {
                $lines[] = $this->getVille();
            }
        }
        return $lines;
    }

    public function getMaplink(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.urlencode(trim($this->getAddress().' '.$this->getCodePostal().' '.$this->getVille()));
    }

    public function setAddress(string $address): static
    {
        $this->mainlink = $address;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->mainlink;
    }

    public function setLines(array $lines): static
    {
        $this->mainlink = implode("\n", $lines);
        return $this;
    }

    public function getLines(): array
    {
        return explode("\n", $this->mainlink);
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setGps(?array $gps): static
    {
        $this->gps = $gps;
        return $this;
    }

    public function getGps(): ?array
    {
        return $this->gps;
    }

}