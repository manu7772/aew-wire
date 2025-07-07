<?php
namespace Aequation\WireBundle\Dto\interfaace;

// PHP
use stdClass;
use Stringable;

interface WireEntityDtoInterface extends Stringable
{
    public function __construct(array|string|stdClass $data, array $context = []);
}