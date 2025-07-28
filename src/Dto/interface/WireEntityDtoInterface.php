<?php
namespace Aequation\WireBundle\Dto\interface;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// PHP
use Stringable;

interface WireEntityDtoInterface extends Stringable
{
    public function __construct(mixed $data, WireEntityManagerInterface $wireEm, array $base_options = []);
    public function toArray(): array;
    public function getOptions(string $attr): array;
    public function getOption(string $attr, string $option): mixed;
    public static function transformFromDto(mixed $value, mixed $source): mixed;
    public static function transformToDto(mixed $value, mixed $source): mixed;

}