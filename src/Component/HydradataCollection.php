<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Files;
// PHP
use RuntimeException;
use SplFileInfo;

class HydradataCollection extends TypedCollection implements HydradataCollectionInterface
{
    
    public readonly WireEntityManagerInterface $wireEm;
    public readonly array $hydratable_names;

    public function __construct(
        public false|string $path,
        public readonly HydrationServiceInterface $hydrator,
        ?array $elements = null
    ) {
        $this->wireEm = $hydrator->wireEm;
        // hydratable_names = array <classname => shortname>
        $this->hydratable_names = $this->hydrator->getHydratableClasses()->mapSingleValue('shortname');
        if(is_array($elements)) {
            $this->elements = $elements;
        } else {
            $this->elements = [];
            // 1. initialize the collection from hydradata files
            if($this->path) {
                foreach ($this->hydrator->getDataFiles($this->path) as $file) {
                    /** @var SplFileInfo $file */
                    if(!$this->registerDataFile($file)) {
                       throw new RuntimeException(vsprintf('Error %s line %d: file "%s" is not a valid hydradata file.', [__METHOD__, __LINE__, $file->getRealPath()]));
                    }
                }
            }
            // 2. initialize the collection from remaining hydratable classes
            foreach (array_keys($this->hydratable_names) as $classname) {
                $this->registerClass($classname);
            }
        }
        $this->sortElements();
    }

    protected function registerClass(string $classname): bool
    {
        // Check if the class is already registered (with file or not, anyway)
        if($this->getByName($classname)) {
            // Already registered
            return true;
        }
        $this->elements[] = new HydradataItems($classname, $this);
        $this->sortElements(); // Already set at the end, no need to sort
        return true;
    }

    public function registerDataFile(string|SplFileInfo $file): bool
    {
        if(is_string($file)) {
            $file = Files::getSplFileInfo($file);
        }
        foreach ($this->elements as $item) {
            if($item->getFile() && ($item->getFile()->getRealpath() === $file->getRealPath())) {
                // Already registered
                return true;
            }
        }
        // Try find any HydradataItemsInterface with the same class name, but without file
        $test = new HydradataItems($file, $this);
        foreach ($this->elements as $item) {
            if($item->name === $test->name && $item->isModeInfo()) {
                // Found a HydradataItemsInterface with the same name but without file
                $item->setFile($file);
                $this->sortElements();
                return true;
            }
        }
        // No HydradataItemsInterface found, create a new one
        $this->elements[] = $test;
        $this->sortElements();
        return true;
    }

    public function sortElements(): static
    {
        $this->sortBy('shortname');
        return $this->sortFn(
            function (HydradataItemsInterface $a, HydradataItemsInterface $b) {
                if(!$a->isValid()) return 3;
                if(!$b->isValid()) return -3;
                if(false === $a->getIndex()) return 2;
                if(false === $b->getIndex()) return -2;
                return $a->getIndex() <=> $b->getIndex();
            }
        );
    }

    protected function createFrom($elements): static
    {
        return new static($this->path, $this->hydrator, $elements);
    }

    public function getByName(string $name): ?HydradataItemsInterface
    {
        foreach ($this->elements as $items) {
            /** @var HydradataItemsInterface $items */
            if($items->isValid() && in_array($name, [$items->name, $items->declared_name], true)) {
                // Already registered
                return $items;
            }
        }
        return null;
    }

    public function getByShortname(string $shortname): ?HydradataItemsInterface
    {
        foreach ($this->elements as $items) {
            /** @var HydradataItemsInterface $items */
            if($items->isValid() && $shortname === $items->getShortname()) {
                // Already registered
                return $items;
            }
        }
        return null;
    }

    public function getByIndex(int $index): ?HydradataItemsInterface
    {
        foreach ($this->elements as $items) {
            /** @var HydradataItemsInterface $items */
            if($items->isValid() && $index === $items->getIndex()) {
                // Already registered
                return $items;
            }
        }
        return null;
    }

    public function isValid(): bool
    {
        return $this->forAll(
            fn (mixed $key, HydradataItemsInterface $element): bool => $element->isValid()
        );
    }

    public function getHydrationReadyIndexes(): array
    {
        // Return indexes of valid HydradataItemsInterface
        return $this->filter(
            fn (HydradataItemsInterface $item) => $item->isHydrationReady()
        )->mapSingleValue('index');
    }

}