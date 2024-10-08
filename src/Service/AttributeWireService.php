<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\interface\AppAttributeMethodInterface;
use Aequation\WireBundle\Attribute\OnEventCall;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\AttributeWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(AttributeWireServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class AttributeWireService extends BaseService implements AttributeWireServiceInterface
{

    public function __construct(
        public readonly AppWireServiceInterface $appWire,
    ) 
    {}

    public static function getClassServiceName(
        string|object $objectOrClass
    ): ?string
    {
        $attrs = Objects::getClassAttributes($objectOrClass, ClassCustomService::class, true);
        if(count($attrs)) {
            $attr = reset($attrs);
            return $attr->service;
        }
        return null;
    }

    public function getClassService(
        string|object $objectOrClass
    ): ?object
    {
        $serviceName = $this->getClassServiceName($objectOrClass);
        return !empty($serviceName) && $this->appWire->has($serviceName)
            ? $this->appWire->get($serviceName)
            : $this->appWire->get(WireEntityManagerInterface::class);
    }

    public function applyPropertyAttribute(
        string $attributeClass,
        WireEntityEvent $event
    ): void
    {
        
    }

    public function applyMethodAttribute(
        string $attributeClass,
        WireEntityEvent $event
    ): void
    {
        $entity = $event->getEntity();
        if(is_a($attributeClass, AppAttributeMethodInterface::class, true)) {
            $attrs = Objects::getClassAttributes($entity, $attributeClass, true);
            foreach ($attrs as $attribute) {
                /** @var AppAttributeMethodInterface $attribute */
                $method = $attribute->getMethod()->name;
                $entity->$method($event);
            }
        } else if($this->appWire->isDev()) {

        }
    }

    /**
     * Apply Event on attribute "OnEventCall"
     *
     * @param string $eventName
     * @param WireEntityEvent $event
     * @return void
     */
    public function applyEventCall(
        string $eventName,
        WireEntityEvent $event
    ): void
    {
        $entity = $event->getEntity();
        $attrs = Objects::getMethodAttributes($entity, OnEventCall::class, true);
        foreach ($attrs as $attribute) {
            /** @var OnEventCall $attribute */
            if($attribute->hasEvent($eventName)) {
                /** @var AppAttributeMethodInterface $attribute */
                $method = $attribute->getMethod()->name;
                $entity->$method($event);
            }
        }
    }


}