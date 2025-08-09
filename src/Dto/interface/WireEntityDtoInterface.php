<?php
namespace Aequation\WireBundle\Dto\interface;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// PHP
use Stringable;

interface WireEntityDtoInterface extends Stringable
{
    // public function __construct(mixed $data, WireEntityManagerInterface $wireEm, array $base_options = []);
    public function toArray(): array;
    public function insertData(array $data): void;
    public function getOptions(string $attr): array;
    public function getOption(string $attr, string $option): mixed;
}