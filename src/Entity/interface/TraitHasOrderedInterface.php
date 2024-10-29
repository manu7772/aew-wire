<?php
namespace Aequation\WireBundle\Entity\interface;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Event\WireEntityEvent;
use ReflectionProperty;

interface TraitHasOrderedInterface extends TraitInterface
{
    // public const ITEMS_ACCEPT = [
    //     'items' => [WireItem::class],
    // ];

    public function __construct_hasOrdered(): void;
    public function updateRelationOrder(WireEntityEvent $event): bool;
    public function RelationOrderOnLoad(WireEntityEvent $event): static;
    public function loadedRelationOrder(WireEntityEvent $event = null, array $params = [], bool $force = false): static;
    public function getRelationOrderDetails(): string;
    public function getRelationOrderNames(string|ReflectionProperty|null $property = null): array;
    public function getRelationOrder(): array;
    public function getPropRelationOrder(string|ReflectionProperty $property): array;

}