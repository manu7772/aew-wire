<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\HydraItemInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Encoders;
// PHP
use SplFileInfo;
use RuntimeException;

class HydradataItems extends TypedCollection implements HydradataItemsInterface
{
    public const MODES = ['info','hydration'];

    protected ?SplFileInfo $file = null;
    protected ?array $file_data = null;
    // States
    protected int $info_state = 0b00000000;
    protected int $file_state = 0b00000000;
    protected int $data_state = 0b00000000;
    public readonly string|false $name;
    public readonly WireClassMetadataInterface|false $wCmd;
    public readonly string $declared_name;
    protected int|false $index;
    protected int|false $order;
    protected bool $enabled;
    public readonly WireEntityManagerInterface $wireEm;
    // public readonly array $hydratable_names;
    // Elements for child CreateFrom
    public array $filtered_elements;

    public function __construct(
        string|SplFileInfo $nameOrFile,
        public readonly HydradataCollectionInterface $collection,
        protected ?self $parent = null,
    ) {
        $this->wireEm = $this->collection->wireEm;
        // $this->hydratable_names = $this->collection->hydratable_names;
        if($this->isFiltered()) {
            // Create child from parent
            // if(!isset($this->parent->filtered_elements)) {
            //     throw new RuntimeException(vsprintf('Error %s line %d: parent collection %s is not filtered, cannot create child from it.', [__METHOD__, __LINE__, $this->parent->name ?: '[invalid parent]']));
            // }
            $this->data_state = $this->parent->getDataState(false); // Copy data state
            $this->file_state = $this->parent->getFileState(false); // Copy file state
            $this->wCmd = $this->parent->wCmd;
            $this->name = $this->parent->name;
            $this->declared_name = $this->parent->declared_name;
            $this->order = $this->parent->getOrder();
            $this->index = $this->parent->getIndex();
            $this->elements = $this->parent->filtered_elements;
            $this->enabled = $this->parent->enabled;
            // $this->checkDatas();
        } else {
            // Root
            if($nameOrFile instanceof SplFileInfo) {
                // mode: hydration
                $this->setFile($nameOrFile);
            } else {
                // mode: info
                $this->declared_name = $nameOrFile;
                $this->wCmd = $this->wireEm->getEntitiesMetadata()->findOneOrNullFinal([$this->declared_name, WireHydratable::class]) ?: false;
                $this->name = $this->wCmd ? $this->wCmd->name : false;
                $this->setDefaultsEmptyData();
            }
        }
        $this->checkInfo();
    }

    public function __toString(): string
    {
        return static::class.'@'.$this->declared_name;
    }

    public function getShortname(): string
    {
        return $this->wCmd ? $this->wCmd->getShortName() : '';
    }

    public function isModeInfo(): string
    {
        return !$this->isModeHydration();
    }

    public function isModeHydration(): string
    {
        return $this->file instanceof SplFileInfo;
    }

    public function isRoot(): bool
    {
        return !$this->parent;
    }

    public function isFiltered(): bool
    {
        return !$this->isRoot();
    }

    public function getWcmd(): WireClassMetadataInterface|false
    {
        return $this->wCmd;
    }

    public function getFile(): ?SplFileInfo
    {
        return $this->file;
    }

    public function setFile(?SplFileInfo $file): static
    {
        if($this->file?->getRealPath() !== $file?->getRealPath()) {
            $this->file = $file;
            $this->updateFile();
        }
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        if($this->enabled !== $enabled) {
            $this->enabled = $enabled;
            $this->data_state &= ~static::DATA_STATES['disabled']; // Reset disabled state
            if(!$this->enabled) {
                $this->data_state |= static::DATA_STATES['disabled'];
            }
        }
        return $this;
    }

    protected function updateFile(): void
    {
        if($this->file) {
            $this->file_data = $this->checkFile()
                ? $this->collection->hydrator->parseYamlData(file_get_contents($this->file->getPathname()))
                : [];
            // dump(array_merge($this->file_data, ['initial_name' => $this->declared_name ?? false]));
            // Set defaults file data
            $file_entity = $this->file_data['entity'] ?? false;
            if($file_entity && !empty($this->declared_name ?? null)) {
                // Declared name is set, so we can use it
                if(!is_a($file_entity, $this->declared_name, true)) {
                    // Declared name is not the same as file data entity
                    throw new RuntimeException(vsprintf('Error %s line %d: the file data entity "%s" does not match (or is not instance of) the initial declared name "%s".', [__METHOD__, __LINE__, $file_entity, $this->declared_name]));
                }
            } else {
                $this->declared_name ??= $file_entity;
                $this->wCmd ??= $this->declared_name ? ($this->wireEm->getEntitiesMetadata()->findOneOrNullFinal([$this->declared_name, WireHydratable::class]) ?: false) : false;
                $this->name ??= $this->wCmd ? $this->wCmd->name : false;
            }
            $this->index = $this->order = $this->file_data['order'] ?? false;
            $this->elements = $this->file_data['items'] ?? [];
            $this->enabled = $this->file_data['enabled'] ?? true;
            if($this->checkDatas()) {
                foreach ($this->elements as $key => $data) {
                    $this->elements[$key] = new HydraItem($data, $this, $key);
                }
            } else {
                // If data is not valid, reset elements
                $this->setDefaultsEmptyData();
            }
        } else {
            // Set/reset to defaults
            $this->setDefaultsEmptyData();
        }
    }

    protected function setDefaultsEmptyData(): void
    {
        $this->file = null;
        $this->file_data = null;
        $this->order = false;
        $this->index = false;
        $this->elements = [];
        $this->enabled = true;
    }

    protected function createFrom($elements): static
    {
        $this->filtered_elements = $elements;
        $self = new static($this->file ?? $this->declared_name, $this->collection, $this);
        unset($self->filtered_elements); // Remove filtered elements from child
        return $self;
    }


    /*******************************************************************************************
     * ORDER / INDEX
     */

    public function getOrder(): int|false
    {
        return $this->order;
    }

    public function getIndex(): int|false
    {
        return $this->index;
    }


    /*******************************************************************************************
     * STATES
     */

    public function getPersisteds(): static
    {
        return $this->filter(
            fn (HydraItemInterface $item) => $item->hasPersisted()
        );
    }

    public function getNews(): static
    {
        return $this->filter(
            fn (HydraItemInterface $item) => !$item->hasPersisted()
        );
    }


    /*******************************************************************************************
     * STATES
     */

    public function getFileState(bool $asBin = false): int|string
    {
        return $asBin ? Encoders::toBin($this->file_state) : $this->file_state;
    }

    public function getDataState(bool $asBin = false): int|string
    {
        return $asBin ? Encoders::toBin($this->data_state) : $this->data_state;
    }

    protected function checkInfo(): bool
    {
        $this->info_state = 0b00000000; // Reset info state
        if(empty($this->wCmd ?? null)) {
            $this->info_state |= static::INFO_STATES['entity_unknown'];
        } else {
            if(!$this->wCmd->isHydratable()) {
                $this->info_state |= static::INFO_STATES['not_hydratable'];
            }
            if(empty($this->wCmd->getDtoSourceMaps())) {
                $this->info_state |= static::INFO_STATES['dto_source_missing'];
            }
            if(empty($this->wCmd->getDtoTargetMaps())) {
                $this->info_state |= static::INFO_STATES['dto_target_missing'];
            }
        }
        return $this->isValid();
    }

    protected function checkFile(): bool
    {
        $this->file_state = 0b00000000; // Reset file state
        if(!is_null($this->file)) {
            if(!$this->file->isFile()) {
                $this->file_state |= static::FILE_STATES['file_not_found'];
            } else if(!$this->file->isReadable()) {
                $this->file_state |= static::FILE_STATES['file_not_readable'];
            }
        }
        return $this->isValid();
    }

    protected function checkDatas(): bool
    {
        $this->data_state = 0b00000000; // Reset data state
        if($this->isModeHydration()) {
            // If file is set, check data
            if(empty($this->file_data ?? [])) {
                $this->data_state |= static::DATA_STATES['data_empty'];
            } else {
                foreach (static::DATA_FIELDS as $name) {
                    if(!isset($this->file_data[$name])) {
                        $this->data_state |= static::DATA_STATES['format_invalid'];
                        break;
                    }
                }
                if(!($this->file_data['enabled'] ?? true)) {
                    $this->data_state |= static::DATA_STATES['disabled'];
                }
                if(!is_int($this->file_data['order'] ?? null)) {
                    $this->data_state |= static::DATA_STATES['order_undefined'];
                }
                if(!is_a($this->file_data['entity'] ?? null, WireHydratable::class, true)) {
                    $this->data_state |= static::DATA_STATES['entity_invalid'];
                }
            }
        }
        if(!$this->isValid()) {
            $this->invalidateData();
        }
        return $this->isValid();
    }

    public function isFileNotFound(): bool
    {
        return ($this->file_state & static::FILE_STATES['file_not_found']) > 0;
    }
    
    public function isFileNotReadable(): bool
    {
        return ($this->file_state & static::FILE_STATES['file_not_readable']) > 0;
    }

    public function isDataEmpty(): bool
    {
        return ($this->data_state & static::DATA_STATES['data_empty']) > 0;
    }
    
    public function isFormatInvalid(): bool
    {
        return ($this->data_state & static::DATA_STATES['format_invalid']) > 0;
    }

    public function isDisabled(): bool
    {
        return ($this->data_state & static::DATA_STATES['disabled']) > 0;
    }

    public function isOrderUndefined(): bool
    {
        return ($this->data_state & static::DATA_STATES['order_undefined']) > 0;
    }

    public function isIndexUndefined(): bool
    {
        return $this->isOrderUndefined();
    }

    public function isEntityInvalid(): bool
    {
        return ($this->data_state & static::DATA_STATES['entity_invalid']) > 0;
    }


    /*******************************************************************************************
     * VALID STATES
     */

    protected function invalidateData(): void
    {
        $this->declared_name ??= false;
        $this->wCmd ??= false;
        $this->name ??= false;
        $this->index ??= false;
        $this->order ??= false;
        $this->elements = [];
        $this->enabled ??= false;
    }

    /**
     * Global validation of the hydradata item.
     */
    public function isValid(): bool
    {
        $base_info = $this->info_state & (static::INFO_STATES['entity_unknown'] | static::INFO_STATES['not_hydratable']);
        return ($base_info | $this->file_state | $this->data_state) === 0b00000000;
    }

    public function isInfoValid(): bool
    {
        return $this->info_state === 0b00000000;
    }

    public function isFileValid(): bool
    {
        return $this->file_state === 0b00000000;
    }

    public function isDataValid(): bool
    {
        return $this->data_state === 0b00000000;
    }

    /**
     * Check if the hydradata item is hydratable and hydration data is valid, so *can hydrate entities now*.
     * Checks if hydration data from file is valid and not missing.
     * 
     * @return bool True if the item can be used to hydrate entities, false otherwise.
     */
    public function isHydrationReady(): bool
    {
        return
            $this->isHydratable()
            && $this->isModeHydration()
            ;
    }

    /**
     * Check if the classname is hydratable, so *can be used to hydrate entities*.
     * Does not check if the hydration data is valid.
     * 
     * @return bool True if the item can be used to hydrate entities, false otherwise.
     */
    public function isHydratable(): bool
    {
        return
            $this->isValid()
            && $this->wCmd->getDtoSourceMaps()
            && $this->wCmd->getDtoTargetMaps()
            ;
    }

    public function getInvalidReasons(): array
    {
        $reasons = [];
        foreach (static::INFO_STATES as $reason => $state) {
            if($this->info_state & $state) {
                $reasons[] = $reason;
            }
        }
        foreach (static::FILE_STATES as $reason => $state) {
            if($this->file_state & $state) {
                $reasons[] = $reason;
            }
        }
        foreach (static::DATA_STATES as $reason => $state) {
            if($this->data_state & $state) {
                $reasons[] = $reason;
            }
        }
        return $reasons;
    }


}