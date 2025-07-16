<?php
namespace Aequation\WireBundle\Dto\interfaace;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// PHP
use Stringable;

interface WireEntityDtoInterface extends Stringable
{
    public function __construct(array $data, WireEntityManagerInterface $wireEm);
    public function createRelations(bool $_createRelations): static;
    public function isCreateRelations(): bool;
    public function integrateData(array $data): void;
}