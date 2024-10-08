<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Event\WireEntityEvent;

interface AttributeWireServiceInterface extends WireServiceInterface
{

    public static function getClassServiceName(string|object $objectOrClass): ?string;
    public function applyPropertyAttribute(string $attributeClass, WireEntityEvent $event): void;
    public function applyMethodAttribute(string $attributeClass, WireEntityEvent $event): void;
    public function applyEventCall(string $eventName, WireEntityEvent $event): void;

}