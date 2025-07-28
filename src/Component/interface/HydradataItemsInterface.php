<?php
namespace Aequation\WireBundle\Component\interface;

use SplFileInfo;

interface HydradataItemsInterface extends TypedCollectionInterface
{

    public const DATA_FIELDS = ['entity', 'order', 'items']; // 'enabled' is optional, default is true
    public const FILE_STATES = [
        'file_not_found'        => 0b00000001,
        'file_not_readable'     => 0b00000010,
    ];
    public const DATA_STATES = [
        'data_empty'            => 0b00000001,
        'format_invalid'        => 0b00000010,
        'disabled'              => 0b00000100,
        'order_undefined'       => 0b00001000,
        'entity_invalid'        => 0b00010000,
    ];
    public const INFO_STATES = [
        'entity_unknown'        => 0b00000001,
        'not_hydratable'        => 0b00000010,
        'dto_target_missing'    => 0b00000100,
        'dto_source_missing'    => 0b00001000,
    ];
    
    public function getShortname(): string;
    /** Mode info: does not contains file data */
    public function isModeInfo(): string;
    /** Mode hydration: contains file data */
    public function isModeHydration(): string;
    /** Is root */
    public function isRoot(): bool;
    /** Is a child: filtered of it's parent */
    public function isFiltered(): bool;
    /** Get wCmd */
    public function getWcmd(): WireClassMetadataInterface|false;
    /** File data */
    public function getFile(): ?SplFileInfo;
    public function setFile(?SplFileInfo $file): static;
    /** Enabled */
    public function isEnabled(): bool;
    public function setEnabled(bool $enabled): static;
    /** Order/index */
    public function getOrder(): int|false;
    public function getIndex(): int|false;
    /** File states */
    public function isFileNotFound(): bool;
    public function isFileNotReadable(): bool;
    /** Data states */
    public function isDataEmpty(): bool;
    public function isFormatInvalid(): bool;
    public function isDisabled(): bool;
    public function isOrderUndefined(): bool;
    public function isIndexUndefined(): bool;
    public function isEntityInvalid(): bool;
    /** States reporting */
    public function getFileState(bool $asBin = false): int|string;
    public function getDataState(bool $asBin = false): int|string;
    /** Validation */
    public function isValid(): bool;
    public function isFileValid(): bool;
    public function isDataValid(): bool;
    public function isHydrationReady(): bool;
    public function isHydratable(): bool;
    public function getInvalidReasons(): array;
}