<?php
namespace Aequation\WireBundle\Entity;

use Aequation\WireBundle\Entity\interface\TwigfileInterface;
use Aequation\WireBundle\Tools\Files;
// Symfony
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable()]
class Twigfile implements TwigfileInterface
{

    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $name = null;

    /**
     * Twigfile constructor.
     *
     * @param string $name
     * @param string $path
     */
    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        protected ?string $path = null
    ) {
        $this->setPath($this->path);
    }

    public function __toString()
    {
        return $this->path ?? '';
    }

    public function toArray(): array
    {
        return [
            // 'name' => $this->name,
            'path' => $this->path,
        ];
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setPath(?string $path): static
    {
        $this->path = empty($path) ? null : $path;
        $this->name = empty($this->path) ? null : Files::stripTwigfile($this->path, true);
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function isEmpty(): bool
    {
        return
            empty($this->name) &&
            empty($this->path)
            ;
    }

}