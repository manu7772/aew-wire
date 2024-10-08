<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Event\WireEntityEvent;
// PHP
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class OnEventCall extends BaseMethodAttribute
{

    public const AVAILABLE_EVENTS = [
        WireEntityEvent::POST_CREATE,
        WireEntityEvent::POST_MODEL,
        WireEntityEvent::POST_CLONE,
        WireEntityEvent::BEFORE_PERSIST,
        WireEntityEvent::BEFORE_UPDATE,
        WireEntityEvent::BEFORE_REMOVE,
    ];

    public array $events = [];

    public function __construct(
        array $events
    )
    {
        $this->setEvents($events);
    }

    public function getAvailableEvents(): array
    {
        return static::AVAILABLE_EVENTS;
    }

    public function setEvents(
        array $events
    ): static
    {
        $this->events = [];
        foreach ($events as $event) {
            if(in_array($event, static::AVAILABLE_EVENTS)) $this->events[] = $event;
        }
        $this->events = array_unique($this->events);
        return $this;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function hasEvent(
        string $event
    ): bool
    {
        return in_array($event, $this->events);
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $parent['events'] = $this->getEvents();
        return $parent;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->setEvents($data['events']);
    }

}