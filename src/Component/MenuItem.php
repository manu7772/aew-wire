<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\MenuComponentInterface;
use Aequation\WireBundle\Component\interface\MenuItemInterface;
use Aequation\WireBundle\Component\interface\RouterInfoInterface;
use Aequation\WireBundle\Tools\Objects;
use InvalidArgumentException;
// Symfony
// PHP
use Stringable;
use Traversable;

class MenuItem implements MenuItemInterface
{

    public readonly string $name;
    public readonly int $level;
    public readonly string $itemType;
    public readonly MenuComponentInterface $rootParent;

    public function __construct(
        protected Traversable|array $itemdata,
        public readonly RouterInfoInterface $router_info,
        public readonly MenuComponentInterface|MenuItemInterface $parent
    ) {
        $this->rootParent = $this->parent->getRootParent();
        switch (true) {
            case is_object($this->itemdata):
                if(!($this->itemdata instanceof Stringable)) {
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: item data must be a Stringable object.', [__METHOD__, __LINE__]));
                }
                $this->name = $this->itemdata->__toString();
                $this->itemType = Objects::getClassname($this->itemdata);
                break;
            case is_array($this->itemdata):
                if(!isset($this->itemdata['name']) || !is_string($this->itemdata['name'])) {
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: item data must contain a "name" key with a string value.', [__METHOD__, __LINE__]));
                }
                $this->name = $this->itemdata['name'];
                $this->itemType = gettype($this->itemdata);
                break;
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getRootParent(): MenuComponentInterface
    {
        return $this->rootParent;
    }

    public function isContextFilterEnabled(): bool
    {
        return $this->rootParent->isContextFilterEnabled();
    }

    /***************************************************************************************************/
    /** Simulation for TypedCollection                                                                 */
    /***************************************************************************************************/

    public function count(): int
    {
        return $this->itemdata instanceof MenuComponent ? $this->itemdata->count() : 0;
    }

}