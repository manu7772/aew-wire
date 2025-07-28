<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\MenuComponentInterface;
use Aequation\WireBundle\Component\interface\MenuItemInterface;
use Aequation\WireBundle\Component\interface\RouterInfoInterface;
use Aequation\WireBundle\Interface\MenuShapeInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// PHP
use Iterator;

class MenuComponent extends TypedCollection implements MenuComponentInterface
{
    public const MAX_LEVELS = 3;

    protected MenuComponentInterface $parent;
    public readonly AppWireServiceInterface $appWire;
    public bool $contextFilterEnabled = true;
    public int $max_levels;

    /**
     * Initializes a new MenuComponent.
     *
     * @phpstan-param array<TKey,T> $elements
     */
    public function __construct(
        MenuShapeInterface|array|null $elements = [],
        public readonly RouterInfoInterface $RouterInfo
    )
    {
        $this->appWire = $this->RouterInfo->getAppWire();
        $this->max_levels = static::MAX_LEVELS;
        $this->elements = [];
        if(count($elements) > 0) {
            // Add items to the collection
            foreach ($elements as $key => $value) {
                $this->set($key, $value);
            }
        }
    }

    public function contextFilter(bool $enabled): static
    {
        $this->contextFilterEnabled = $enabled;
        return $this;
    }

    public function isContextFilterEnabled(): bool
    {
        return $this->contextFilterEnabled;
    }

    public function getRouterInfo(): RouterInfoInterface
    {
        return $this->RouterInfo;
    }

    public function getAppWire(): AppWireServiceInterface
    {
        return $this->appWire;
    }

    public function getMaxLevels(): int
    {
        return $this->max_levels;
    }

    public function setMaxLevels(int $max_levels): static
    {
        $this->max_levels = $max_levels;
        return $this;
    }

    public function getLevel(): int
    {
        return 0; // Root level is always 0
    }

    public function getRootParent(): MenuComponentInterface
    {
        return $this;
    }


    /***************************************************************************************************/
    /** Override TypedCollection                                                                       */
    /***************************************************************************************************/

    public function set(string|int $key, mixed $item): void
    {
        if (!($item instanceof MenuItemInterface) && !($item instanceof MenuComponentInterface)) {
            $item = new MenuItem($item, $this->RouterInfo, $this);
        }
        parent::set($key, $item);
    }

    public function add(mixed $item): void
    {
        if (!($item instanceof MenuItemInterface) && !($item instanceof MenuComponentInterface)) {
            $item = new MenuItem($item, $this->RouterInfo, $this);
        }
        parent::add($item);
    }




}