<?php
namespace Aequation\WireBundle\Component\interface;

interface HydradataItemsInterface extends TypedCollectionInterface
{
        public const STATES = [
        'file_not_readable' => 0b00000001,
        'file_invalid'      => 0b00000010,
        'data_empty'        => 0b00000100,
        'format_invalid'    => 0b00001000,
        'disabled'          => 0b00010000,
        'name_undefined'    => 0b00100000,
        'order_undefined'   => 0b01000000,
        'name_unknown'      => 0b10000000,
    ];

    public function getOrder(): int|false;
    public function getIndex(): int|false;
    public function getState(bool $asBin = false): int|string;
    public function isExactBinState(int $state): bool;
    public function isExactState(string $state): bool;
    public function isFileNotReadable(): bool;
    public function isFileInvalid(): bool;
    public function isFormatInvalid(): bool;
    public function isDataEmpty(): bool;
    public function isDisabled(): bool;
    public function isNameUndefined(): bool;
    public function isOrderUndefined(): bool;
    public function isNameUnknown(): bool;
    public function isValid(): bool;
    public function isRegisterable(): bool;
    public function getInvalidReasons(): array;
    public function isHydratable(): bool;
}