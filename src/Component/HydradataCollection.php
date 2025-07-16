<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use RuntimeException;
// Symfony
use Symfony\Component\Finder\SplFileInfo;

class HydradataCollection extends TypedCollection implements HydradataCollectionInterface
{
    
    public readonly WireEntityManagerInterface $wireEm;

    public function __construct(
        public string $path,
        public readonly HydrationServiceInterface $hydrator,
        ?array $elements = null
    ) {
        $this->wireEm = $hydrator->wireEm;
        if(is_array($elements)) {
            $this->elements = $elements;
        } else {
            $datas = [];
            foreach ($this->hydrator->getDataFiles($this->path) as $file) {
                /** @var SplFileInfo $file */
                $hd = new HydradataItems($file, $this);
                if($hd->isRegisterable()) {
                    if(isset($datas[$hd->getIndex()])) {
                        dump($hd, $hd->getInvalidReasons(), $datas[$hd->getIndex()]);
                        throw new RuntimeException(sprintf('Hydradata "%s" already exists with order %d, please check your data files.', $hd->name, $hd->getIndex()));
                    }
                    $datas[$hd->getIndex()] = $hd;
                }
            }
            ksort($datas);
            $this->elements = $datas;
        }
    }

    protected function createFrom($elements): static
    {
        return new static($this->path, $this->hydrator, $elements);
    }

    public function getByName(string $name): static
    {
        return $this->filter(
            fn (HydradataItemsInterface $items) => in_array($name, [$items->name, $items->declared_name], true)
        );
    }

    public function isValid(): bool
    {
        return $this->forAll(
            fn (mixed $key, HydradataItemsInterface $element): bool => $element->isValid()
        );
    }

    public function getValidIndexes(): array
    {
        // Return indexes of valid HydradataItemsInterface
        return $this->filter(
            fn (HydradataItemsInterface $item) => $item->isHydratable()
        )->mapSingleValue('index');
    }

}