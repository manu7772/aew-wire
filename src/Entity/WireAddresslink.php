<?php
namespace Aequation\WireBundle\Entity;

// Aequation

use Aequation\WireBundle\Component\ArrayTextUtil;
use Aequation\WireBundle\Component\interface\ArrayTextUtilInterface;
use Aequation\WireBundle\Doctrine\Type\ArrayTextType;
use Aequation\WireBundle\Entity\interface\WireAddresslinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name','ownereuid'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WireAddresslink extends WireRelink implements WireAddresslinkInterface
{

    public const ICON = [
        'ux' => 'tabler:map-pin',
        'fa' => 'fa-link'
    ];
    public const RELINK_TYPE = 'ADDRESS';

    #[Assert\NotNull(message: 'L\'adresse est obligatoire', groups: ['persist','update'])]
    #[Assert\NotBlank(message: 'L\'adresse ne peut être vide', groups: ['persist','update'])]
    protected ?string $mainlink = null;

    #[ORM\Column(name: '`lines`', type: ArrayTextType::NAME, nullable: false)]
    protected ArrayTextUtilInterface $lines;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    // #[Assert\NotNull(message: 'La ville est obligatoire', groups: ['persist','update'])]
    #[Assert\NoSuspiciousCharacters(groups: ['persist','update'])]
    protected ?string $ville = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    // #[Assert\NotNull(message: 'Le code postal est obligatoire', groups: ['persist','update'])]
    #[Assert\Regex(pattern: '/^[0-9]{4,5}$/', message: 'Le code postal doit contenir entre 4 et 5 chiffres', groups: ['persist','update'])]
    protected ?string $codePostal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $url = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $gps = null;


    public function __construct()
    {
        parent::__construct();
        $this->lines = new ArrayTextUtil();
    }

    public function getALink(
        ?int $referenceType = null
    ): ?string
    {
        return $this->getMaplink();
    }

    public function getAddressLines(bool $joinCPandVille = true): ArrayTextUtilInterface
    {
        $lines = clone $this->getLines();
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
        return $this->setLines(Strings::split_lines($address));
    }

    public function setMainlink(string $mainlink): static
    {
        $this->setAddress($mainlink);
        return $this;
    }

    public function getAddress(): string
    {
        return $this->lines->toString(' ');
    }

    /**
     * Set the address lines
     * 
     * @param array $lines
     * @return static
     */
    public function setLines(array $lines): static
    {
        // $this->lines->setAll(array_unique(Strings::split_lines(implode(Strings::LINE_SEPARATOR, $lines))));
        $this->lines->setAll($lines);
        $this->mainlink = $this->getAddress();
        return $this;
    }

    public function getLines(): ArrayTextUtilInterface
    {
        return $this->lines;
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
        return !empty($this->codePostal) ? str_pad($this->codePostal, 5, '0', STR_PAD_LEFT) : null;
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

    public function setGps(null|string|array $gps): static
    {
        $this->gps = Encoders::split_gps($gps);
        return $this;
    }

    public function getGps(): ?array
    {
        return $this->gps;
    }

}