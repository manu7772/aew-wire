<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\HydraItemInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Interface\WireHydratable;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Strings;
use RuntimeException;
// Symfony
use Symfony\Component\Finder\SplFileInfo;

class HydradataItems extends TypedCollection implements HydradataItemsInterface
{

 
    protected int $state = 0b00000000;
    public readonly string $name;
    public readonly ?WireClassMetadataInterface $wCmd;
    public readonly string $declared_name;
    protected int|false $index;
    protected int|false $order;
    protected bool $enabled;
    public readonly WireEntityManagerInterface $wireEm;
    public readonly array $hydratable_names;
    public readonly array $all_names;
    // Elements for child CreateFrom
    public array $filtered_elements;

    public function __construct(
        public readonly SplFileInfo $file,
        public readonly HydradataCollectionInterface $collection,
        protected ?self $parent = null,
    ) {
        $this->wireEm = $this->collection->wireEm;
        $this->hydratable_names = $this->wireEm->getEntitiesMetadata()->findFinals([WireHydratable::class])->mapSingleValue('shortname');
        $this->all_names = $this->wireEm->getEntitiesMetadata()->findFinals()->mapSingleValue('shortname');
        $this->loadFile();
    }

    protected function loadFile(): void
    {
        if($this->parent) {
            // Create child from parent
            if(!isset($this->parent->filtered_elements)) {
                throw new RuntimeException(vsprintf('Error %s line %d: parent collection %s is not filtered, cannot create child from it.', [__METHOD__, __LINE__, $this->parent->name ?: '[invalid parent]']));
            }
            $this->state = $this->parent->getState(false); // Copy state
            $this->wCmd = $this->parent->wCmd;
            $this->name = $this->parent->name;
            $this->declared_name = $this->parent->declared_name;
            $this->order = $this->parent->getOrder();
            $this->index = $this->parent->getIndex();
            $this->elements = $this->parent->filtered_elements;
            $this->enabled = $this->parent->enabled;
            $this->checkStates();
        } else {
            // Create root from file
            if($this->checkFile()) {
                $data = $this->collection->hydrator->parseYamlData($this->file->getContents());
                // Set data
                $data['entity'] ??= false;
                $this->declared_name = $data['entity'];
                if(!in_array($this->declared_name, $this->hydratable_names)) {
                    // Not hydratable, try to find one hydratable subclass
                    $this->wCmd = $this->wireEm->getEntitiesMetadata()->findOneOrNullFinal([$this->declared_name, WireHydratable::class]);
                    $this->name = $this->wCmd?->name ?: false;
                } else {
                    $this->name = $data['entity'];
                    $this->wCmd = $this->wireEm->getEntityMetadata($this->name);
                }
                $this->index = $this->order = $data['order'] ?? false;
                $this->elements = $data['items'] ?? [];
                $this->enabled = $data['enabled'] ?? true;
            }
            if($this->checkStates()) {
                foreach ($this->elements as $key => $data) {
                    $this->elements[$key] = new HydraItem($data, $this);
                }
            }
        }
    }

    public function __toString(): string
    {
        return static::class.'@'.$this->declared_name;
    }

    protected function createFrom($elements): static
    {
        $this->filtered_elements = $elements;
        $self = new static($this->file, $this->collection, $this);
        unset($self->filtered_elements); // Remove filtered elements from child
        return $self;
    }

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
     * STATES / VALIDATION
     */

    public function getState(bool $asBin = false): int|string
    {
        return $asBin ? Encoders::toBin($this->state) : $this->state;
    }

    protected function checkFile(bool $reset_state = true): bool
    {
        if($reset_state) {
            $this->state = 0b00000000; // Reset state
        }
        if(!$this->file->isFile()) {
            $this->setFileInvalid();
        } else if(!$this->file->isReadable()) {
            $this->setFileNotReadable();
        }
        return $this->isValid();
    }

    protected function checkStates(): bool
    {
        // Other states
        if(empty($this->elements)) {
            $this->setDataEmpty();
        }
        if(empty($this->declared_name)) {
            $this->setNameUndefined();
        }
        if(!is_int($this->order)) {
            $this->setOrderUndefined();
        }
        if($this->isOrderUndefined() || empty($this->name) || $this->isDataEmpty()) {
            $this->setFormatInvalid();
        }
        if(!$this->enabled) {
            $this->setDisabled();
        }
        if(!array_key_exists($this->name, $this->hydratable_names) || !$this->wireEm->entityExists($this->declared_name)) {
            $this->setNameUnknown();
        }
        if(!$this->isValid()) {
            $this->invalidateData();
        }
        return $this->isValid();
    }

    protected function invalidateData(): void
    {
        $this->declared_name ??= false;
        $this->name ??= false;
        $this->index ??= false;
        $this->order ??= false;
        $this->elements = [];
        $this->enabled ??= false;
    }

    public function isExactBinState(int $state): bool
    {
        return $this->state === $state;
    }

    public function isExactState(string $state): bool
    {
        return $this->state === static::STATES[$state];
    }

    protected function setFileNotReadable(): static
    {
        $this->state |= static::STATES['file_not_readable'];
        return $this;
    }

    public function isFileNotReadable(): bool
    {
        return ($this->state & static::STATES['file_not_readable']) > 0;
    }

    protected function setFileInvalid(): static
    {
        $this->state |= static::STATES['file_invalid'];
        return $this;
    }

    public function isFileInvalid(): bool
    {
        return ($this->state & static::STATES['file_invalid']) > 0;
    }

    protected function setFormatInvalid(): static
    {
        $this->state |= static::STATES['format_invalid'];
        return $this;
    }

    public function isFormatInvalid(): bool
    {
        return ($this->state & static::STATES['format_invalid']) > 0;
    }

    protected function setDataEmpty(): static
    {
        $this->state |= static::STATES['data_empty'];
        return $this;
    }

    public function isDataEmpty(): bool
    {
        return ($this->state & static::STATES['data_empty']) > 0;
    }

    protected function setDisabled(): static
    {
        $this->state |= static::STATES['disabled'];
        return $this;
    }

    public function isDisabled(): bool
    {
        return ($this->state & static::STATES['disabled']) > 0;
    }

    protected function setNameUndefined(): static
    {
        $this->state |= static::STATES['name_undefined'];
        return $this;
    }

    public function isNameUndefined(): bool
    {
        return ($this->state & static::STATES['name_undefined']) > 0;
    }

    protected function setOrderUndefined(): static
    {
        $this->state |= static::STATES['order_undefined'];
        return $this;
    }

    public function isOrderUndefined(): bool
    {
        return ($this->state & static::STATES['order_undefined']) > 0;
    }

    protected function setNameUnknown(): static
    {
        $this->state |= static::STATES['name_unknown'];
        return $this;
    }

    public function isNameUnknown(): bool
    {
        return ($this->state & static::STATES['name_unknown']) > 0;
    }

    public function isValid(): bool
    {
        return $this->isExactBinState(0b00000000);
    }

    /**
     * Check if the hydradata item is registerable, so *can be added in the HydradataCollection*.
     * 
     * @return bool True if the item can be registered, false otherwise.
     */
    public function isRegisterable(): bool
    {
        return !$this->isOrderUndefined();
    }

    public function getInvalidReasons(): array
    {
        $reasons = [];
        if($this->isFileNotReadable()) {
            $reasons[] = ucfirst(Strings::stringFormated('file_not_readable', 'spaced'));
        }
        if($this->isFileInvalid()) {
            $reasons[] = ucfirst(Strings::stringFormated('file_invalid', 'spaced'));
        }
        if($this->isDataEmpty()) {
            $reasons[] = ucfirst(Strings::stringFormated('data_empty', 'spaced'));
        }
        if($this->isFormatInvalid()) {
            $reasons[] = ucfirst(Strings::stringFormated('format_invalid', 'spaced'));
        }
        if($this->isDisabled()) {
            $reasons[] = ucfirst(Strings::stringFormated('disabled', 'spaced'));
        }
        if($this->isNameUndefined()) {
            $reasons[] = ucfirst(Strings::stringFormated('name_undefined', 'spaced'));
        }
        if($this->isOrderUndefined()) {
            $reasons[] = ucfirst(Strings::stringFormated('order_undefined', 'spaced'));
        }
        if($this->isNameUnknown()) {
            $reasons[] = ucfirst(Strings::stringFormated('name_unknown', 'spaced'));
        }
        return $reasons;
    }

    /**
     * Check if the hydradata item is hydratable, so *can be used to hydrate entities*.
     * Checks if all elements are valid and not missing.
     * 
     * @return bool True if the item can be used to hydrate entities, false otherwise.
     */
    public function isHydratable(): bool
    {
        if(!$this->isValid()) {
            return false;
        }
        if(empty($this->wCmd->getDtoSourceMaps())) {
            return false;
        }
        if(empty($this->wCmd->getDtoTargetMaps())) {
            return false;
        }
        return true;
    }


}

/**
 * @see https://onlinephp.io/c/29621
 */

/*

$test = 13;
$combine = 1;

$result1 = $test & $combine;
$result2 = $test | $combine;
$result3 = $test ^ $combine;

function toBin(int $num) {
	return str_pad(decbin($num), 8, 0, STR_PAD_LEFT);
}

echo("Test 1 => ".toBin($test)." & ".toBin($combine)." : $result1 (".toBin($result1).")".PHP_EOL);
echo("Test 2 => ".toBin($test)." | ".toBin($combine)." : $result2 (".toBin($result2).")".PHP_EOL);
echo("Test 3 => ".toBin($test)." ^ ".toBin($combine)." : $result3 (".toBin($result3).")".PHP_EOL);

*/