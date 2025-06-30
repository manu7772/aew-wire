<?php
namespace Aequation\WireBundle\Dto;

use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;

class WireMenuCompiledDto
{

    private bool $valid = true;

    // Is current route
    public bool $currentRoute = false;
    // Is filtered
    public bool $filtered = true;
    // Attributes
    public string $name;
    public string $title;
    public bool $active;
    public bool $enabled;
    public ArrayCollection $items;

    public function __construct(
        public WireMenuInterface|WireWebpageInterface $menu,
        public AppWireServiceInterface $appWire,
        public ?WireMenuInterface $parent = null,
        public int $depth = 2
    )
    {
        $this->initialize();
    }

    public function isValid(
        bool $reevaluate = false
    ): bool
    {
        if($reevaluate) {
            $this->valid = $this->depth > 0 && $this->menu->isActive() && !$this->menu->isEmpty() && !$this->appWire->isPublic();
        }
        return $this->valid;
    }

    public function initialize(): void
    {
        if(!$this->isValid(true)) {
            return;
        }
        $this->valid = true;
        $this->name = $this->menu->getName();
        $this->title = $this->menu->getTitle();
        $this->active = $this->menu->isActive();
        $this->enabled = $this->menu->isEnabled();
        $this->constructMenu();
    }


    public function isMenu(): bool
    {
        return $this->menu instanceof WireMenuInterface;
    }

    public function constructMenu(
        bool $filterActives = true
    ): void
    {
        $this->filtered = $filterActives;
        $this->items = new ArrayCollection();
        if($this->isValid(true)) {
            $items = $this->filtered ? $this->menu->getActiveItems() : $this->menu->getItems();
            $this->items = $items->map(fn ($item) => new static($item, $this->appWire, $this->menu, $this->depth - 1))
                ->filter(fn (WireMenuCompiledDto $dto) => $dto->isValid());
        }
        if($this->items->isEmpty()) {
            $this->valid = false;
        }
    }

}