<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\MenuItemInterface;
use Aequation\WireBundle\Component\interface\RouterInfoInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Symfony
use Symfony\Component\String\Slugger\AsciiSlugger;
// PHP
use InvalidArgumentException;

class MenuItem extends TypedCollection implements MenuItemInterface
{
    public const MAX_LEVELS = 3;
    public const DEFAULT_OPTIONS = [
        'fwFilter' => null,
        'turbo' => true,
        'target' => null, // null = auto
    ];

    public readonly int $max_levels;
    public readonly int $level;
    public readonly RouterInfoInterface $router_info;
    public readonly ?MenuItemInterface $rootParent;
    protected AsciiSlugger $slugger;
    // MenuItem data
    public readonly string $title;
    public readonly bool $active;
    public readonly ?string $url;
    public readonly ?string $route;
    public readonly ?string $icon;
    public readonly ?string $class;
    public readonly array $route_params;
    public readonly string $slug;
    public readonly bool $open;

    public function __construct(
        protected object|array $itemdata,
        public array $options,
        public readonly AppWireServiceInterface $appWire,
        public readonly ?MenuItemInterface $parent = null
    )
    {
        $this->slugger = new AsciiSlugger();
        $this->router_info = $this->appWire->getRouterInfo();
        $this->rootParent = $this->parent ? $this->parent->rootParent : null;
        $this->max_levels = $this->parent ? $this->parent->max_levels : static::MAX_LEVELS;
        $this->level = $this->parent ? $this->parent->level + 1 : 0;
        $this->options = $this->parent ? $this->parent->options : array_merge(static::DEFAULT_OPTIONS, $this->options);
        $this->elements = [];
        switch (true) {
            case is_array($itemdata):
                $this->title = $this->options['name'] ?? $itemdata['linktitle'] ?? $itemdata['title'];
                $this->slug = $itemdata['slug'] ?? $this->slugger->slug($this->title);
                $this->active = $itemdata['active'] ?? true;
                $this->compileLink($itemdata);
                if($this->isValid()) {
                    if($this->level < $this->max_levels && count($itemdata['childs'] ?? []) > 0) {
                        foreach ($itemdata['childs'] as $name => $child) {
                            if(is_string($name)) $this->options['name'] = $name;
                            $child = new static($child, $this->options, $this->appWire, $this);
                            if($child->isValid()) {
                                $this->elements[] = $child;
                            }
                        }
                    }
                }
                break;
            case $itemdata instanceof WireEcollectionInterface:
                $this->title = $this->options['name'] ?? ($itemdata instanceof TraitWebpageableInterface ? $itemdata->getLinktitle() : $itemdata->__toString());
                $this->slug = $itemdata->getSlug();
                $this->active = $itemdata->isActive();
                $this->compileLink($itemdata);
                // if($this->isValid()) {
                    if($this->level < $this->max_levels) {
                        foreach ($itemdata->getItems() as $name => $child) {
                            if(is_string($name)) $this->options['name'] = $name;
                            $child = new static($child, $this->options, $this->appWire, $this);
                            if($child->isValid()) {
                                $this->elements[] = $child;
                            }
                        }
                    }
                // }
                break;
            case $itemdata instanceof WireItemInterface:
                $this->title = $this->options['name'] ?? ($itemdata instanceof TraitWebpageableInterface ? $itemdata->getLinktitle() : $itemdata->__toString());
                $this->slug = $itemdata->getSlug();
                $this->active = $itemdata->isActive();
                $this->compileLink($itemdata);
                // if($this->isValid()) {
                    // nothing to do, WireItem cannot have childs
                // }
                break;
            case $itemdata instanceof WireUrlinkInterface:
                $this->title = $this->options['name'] ?? $itemdata->getLinktitle() ?? $itemdata->getName();
                $this->slug = $this->slugger->slug($this->title);
                $this->active = true;
                $this->compileLink($itemdata);
                // if($this->isValid()) {
                    // nothing to do, WireUrlink cannot have childs
                // }
                break;
            case $itemdata instanceof Collection:
                $this->title = $this->options['name'] ?? 'Menu collection';
                $this->slug = 'menu-collection';
                $this->active = true;
                $this->compileLink($itemdata);
                // if($this->isValid()) {
                    if($this->level < $this->max_levels) {
                        foreach ($itemdata as $name => $child) {
                            if(is_string($name)) $this->options['name'] = $name;
                            $child = new static($child, $this->options, $this->appWire, $this);
                            if($child->isValid()) {
                                $this->elements[] = $child;
                            }
                        }
                    }
                // } else {
                //     dd($this->isValid(), $this);
                // }
                break;
            default:
                throw new InvalidArgumentException(vsprintf('Error %s line %d: The provided itemdata %s is not valid.', [__METHOD__, __LINE__, Objects::toDebugString($itemdata)]));
                break;
        }
        $this->isOpen();
        if(!$this->parent) dump($this);
    }

    public function isValid(): bool
    {
        return $this->active && (!empty($this->url ?? null) || !empty($this->elements)); // item without url but with childs is valid (container only)
    }

    public function __toString(): string
    {
        return (string) ($this->title ?? 'n/a');
    }

    public function isOpen(): bool
    {
        if(!isset($this->open)) {
            if($this->router_info->route === $this->route) {
                return $this->open = true;
            }
            foreach ($this->elements as $child) {
                if($child->isOpen()) {
                    return $this->open = true;
                }
            }
            return $this->open = false;
        }
        return $this->open;
    }

    protected function compileLink(object|array $itemdata): void
    {
        switch (true) {
            case is_array($itemdata):
                if(!empty($itemdata['url'] ?? null)) {
                    $this->url = $itemdata['url'];
                }
                if(!empty($itemdata['route'] ?? null)) {
                    $this->route = $itemdata['route'];
                    $this->route_params = $itemdata['route_params'] ?? [];
                    $this->url ??= $this->router_info->getRouter()->generate($this->route, $this->route_params);
                }
                $this->icon = $itemdata['icon'] ?? null;
                $this->class = null;
                break;
            case $itemdata instanceof WireEcollectionInterface:
                $this->route = 'app_menu';
                $this->route_params = ['menu' => $itemdata->getSlug()];
                $this->url = $this->router_info->getRouter()->generate($this->route, $this->route_params);
                $this->icon = $itemdata->temp_icon;
                $this->class = null;
                break;
            // case $itemdata instanceof WireItemInterface:
            case $itemdata instanceof WireWebpageInterface:
                $this->route = 'app_webpage';
                $this->route_params = ['webpage' => $itemdata->getSlug()];
                $this->url = $this->router_info->getRouter()->generate($this->route, $this->route_params);
                $this->icon = $itemdata->temp_icon;
                $this->class = null;
                break;
            case $itemdata instanceof TraitWebpageableInterface && $itemdata->hasWebpage():
                $this->route = 'app_webpage';
                $this->route_params = ['webpage' => $itemdata->getWebpage()->getSlug()];
                $this->url = $this->router_info->getRouter()->generate($this->route, $this->route_params);
                $this->icon = $itemdata->temp_icon;
                $this->class = null;
                break;
            case $itemdata instanceof WireUrlinkInterface:
                $this->route = $itemdata->getRoute();
                $this->route_params = $itemdata->getParams() ?? [];
                $this->url = $itemdata->getUrl();
                $this->icon = $this->options['icon'] ?? null;
                $this->class = null;
                break;
            case $itemdata instanceof Collection:
                $this->route = null;
                $this->route_params = [];
                $this->url = null;
                $this->icon = null;
                $this->class = null;
                break;
            default:
                $this->url = null;
                $this->route = null;
                $this->route_params = [];
                $this->icon = null;
                $this->class = null;
                if($this->appWire->isDev()) {
                    throw new InvalidArgumentException(vsprintf('Error %s line %d: The provided itemdata %s is not supported yet.', [__METHOD__, __LINE__, Objects::toDebugString($itemdata)]));
                }
                break;
        }
    }


}