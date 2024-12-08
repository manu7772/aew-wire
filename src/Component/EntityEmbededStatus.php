<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Entity\interface\TraitClonableInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// Symfony
use Doctrine\ORM\UnitOfWork;
// PHP
use Exception;

/**
 * EntityEmbededStatus
 * Entity status container of useful methods and services
 */
class EntityEmbededStatus implements EntityEmbededStatusInterface
{

    protected int $status;
    public readonly WireEntityManagerInterface $wireEntityManager;
    public readonly WireEntityServiceInterface $wireEntityService;
    public readonly UnitOfWork $uow;
    protected array $dispatchedEvents;

    /**
     * Constructor
     *
     * @param WireEntityInterface $entity
     * @param AppWireServiceInterface $appWire
     */
    public function __construct(
        public readonly WireEntityInterface $entity,
        public readonly AppWireServiceInterface $appWire
    )
    {
        $this->dispatchedEvents = [];
        $this->wireEntityManager = $this->appWire->get(WireEntityManagerInterface::class);
        $this->uow = $this->wireEntityManager->uow;
        $service = $this->wireEntityManager->getEntityService($this->entity);
        if($this->isDev() && !($service instanceof WireEntityServiceInterface)) {
            $message = vsprintf('Error %s line %d:%s- %s named %s service is not instance of %s! (see dumped data in dev navbar).', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->entity->__toString(), WireEntityServiceInterface::class]);
            dump($message, $service, $this);
            throw new Exception($message);
        }
        $this->wireEntityService = $service;
        // $this->entity->setEmbededStatus($this);
        $this->status = static::STATUS_NULL;
    }

    /**
     * Is dev environment
     *
     * @return boolean
     */
    public function isDev(): bool
    {
        return $this->appWire->isDev();
    }

    /**
     * Is prod environment
     *
     * @return boolean
     */
    public function isProd(): bool
    {
        return $this->appWire->isProd();
    }

    /**
     * Get all status
     *
     * @return array
     */
    public static function getAllStatus(): array
    {
        return [
            // 'STATUS_NULL' => static::STATUS_NULL,
            'STATUS_CREATED' => static::STATUS_CREATED,
            'STATUS_LOADED' => static::STATUS_LOADED,
            'STATUS_CLONE' => static::STATUS_CLONE,
            'STATUS_MODEL' => static::STATUS_MODEL,
            'STATUS_FLUSHED' => static::STATUS_FLUSHED,
            'STATUS_DELETED' => static::STATUS_DELETED,
        ];
    }


    /**
     * Check current status.
     * Throw exception if status is not correct.
     * Return array of errors and all status.
     *
     * @param boolean $throwException
     * @return array
     */
    protected function checkStatus(
        bool $throwException = true
    ): array
    {
        $report = ['errors' => [], 'all_status' => []];
        foreach ($this->getAllStatus() as $name => $status) {
            if(($this->status & $status) > 0) {
                $report['all_status'][$status] = $name;
                switch ($status) {
                    case static::STATUS_CREATED:
                        if($this->isLoaded()) $report['errors'][] = 'Can not be created AND loaded';
                        break;
                    case static::STATUS_LOADED:
                        if($this->isCreated()) $report['errors'][] = 'Can not be loaded AND created';
                        break;
                    case static::STATUS_CLONE:
                        if(!$this->isClonable()) $report['errors'][] = 'Not clonable';
                        if(!$this->isCreated()) $report['errors'][] = 'Clone should also be created';
                        break;
                    case static::STATUS_MODEL:
                        if($this->status !== $status) $report['errors'][] = 'Model can be nothing else';
                        break;
                    case static::STATUS_FLUSHED:
                        // if($this->isModel()) $report['errors'][] = 'A model can not have been flushed';
                        break;
                    case static::STATUS_DELETED:
                        // if($this->isModel()) $report['errors'][] = 'A model can not have been deleted';
                        break;
                }
            }
        }
        if($throwException && !empty($report['errors'])) {
            throw new Exception(vsprintf('Error %s line %d:%sEntity %s has status errors%s(Actual status: %s)%s', [__METHOD__, __LINE__, PHP_EOL, $this->entity->getClassname(), PHP_EOL, implode(', ', $report['all_status']), PHP_EOL.'- ', implode(PHP_EOL.'- ', $report['errors'])]));
        }
        return $report;
    }

    /**
     * Get all status as string
     *
     * @param string $separator
     * @return string
     */
    public function getAllStatusAsString(
        string $separator = ', '
    ): string
    {
        $report = $this->checkStatus(false);
        return implode($separator, $report['all_status']);
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Is status empty
     *
     * @return boolean
     */
    public function isEmptyStatus(): bool
    {
        return $this->status === 0;
    }


    /** ENTITY */

    /**
     * Set entity status to CREATED
     *
     * @return static
     */
    public function setCreated(): static
    {
        if($this->isEmptyStatus()) {
            $all_status = $this->getAllStatusAsString(' / ');
            throw new Exception(vsprintf('Error %s line %d:%s- Can only attribute status CREATED on empty status :%sactually, status is %s (%d).', [__METHOD__, __LINE__, PHP_EOL, PHP_EOL, $all_status, $this->status]));
        }
        $this->status = static::STATUS_CREATED;
        $this->checkStatus();
        return $this;
    }

    /**
     * Is entity created
     *
     * @return boolean
     */
    public function isCreated(): bool
    {
        return !empty($this->status & static::STATUS_CREATED);
    }

    /**
     * Set entity status to LOADED
     *
     * @return static
     */
    public function setLoaded(): static
    {
        if($this->isEmptyStatus()) {
            $all_status = $this->getAllStatusAsString(' / ');
            throw new Exception(vsprintf('Error %s line %d:%s- Can only attribute status LOADED on empty status :%sactually, status is %s (%d).', [__METHOD__, __LINE__, PHP_EOL, PHP_EOL, $all_status, $this->status]));
        }
        $this->status = static::STATUS_LOADED;
        $this->checkStatus();
        return $this;
    }

    /**
     * Is entity loaded
     *
     * @return boolean
     */
    public function isLoaded(): bool
    {
        return !empty($this->status & static::STATUS_LOADED);
    }

    /**
     * Set entity status to FLUSHED
     *
     * @return static
     */
    public function setFlushed(): static
    {
        $this->status = $this->status | static::STATUS_FLUSHED;
        $this->checkStatus();
        return $this;
    }

    /**
     * Is entity flushed
     *
     * @return boolean
     */
    public function isFlushed(): bool
    {
        return !empty($this->status & static::STATUS_FLUSHED);
    }

    /**
     * Set entity status to DELETED
     *
     * @return static
     */
    public function setDeleted(): static
    {
        $this->status = $this->status | static::STATUS_DELETED;
        $this->checkStatus();
        return $this;
    }

    /**
     * Is entity deleted
     *
     * @return boolean
     */
    public function isDeleted(): bool
    {
        return !empty($this->status & static::STATUS_DELETED);
    }

    // public function setPostflushed(): static
    // {
    //     if($this->isDev()) $this->checkStatus(static::STATUS_FLUSHED, true);
    //     $this->status = $this->status ^ static::STATUS_FLUSHING; // Remove flushed
    //     $this->status = $this->status | static::STATUS_FLUSHED;
    //     $this->resetDispatchedEvents(WireEntityEvent::BEFORE_UPDATE);
    //     return $this;
    // }


    /** MODEL */

    /**
     * Set entity status to MODEL
     *
     * @return static
     */
    public function setModel(): static
    {
        if($this->isEmptyStatus()) {
            $all_status = $this->getAllStatusAsString(' / ');
            throw new Exception(vsprintf('Error %s line %d:%s- Can only attribute status MODEL on empty status :%sactually, status is %s (%d).', [__METHOD__, __LINE__, PHP_EOL, PHP_EOL, $all_status, $this->status]));
        }
        $this->status = static::STATUS_MODEL;
        $this->checkStatus();
        return $this;
    }

    /**
     * Is entity model
     *
     * @return boolean
     */
    public function isModel(): bool
    {
        return !empty($this->status & static::STATUS_MODEL);
    }


    /** CLONE */

    /**
     * Is entity clonable
     *
     * @return boolean
     */
    public function isClonable(): bool
    {
        return $this->entity instanceof TraitClonableInterface && $this->entity::IS_CLONABLE;
    }

    /**
     * Set entity status to CLONE
     *
     * @return static
     */
    public function setClone(): static
    {
        $this->status = static::STATUS_CLONE | static::STATUS_CREATED; // --> becomes CLONED & ENTITY
        $this->checkStatus();
        return $this;
    }

    /**
     * Is entity clone
     *
     * @return boolean
     */
    public function isClone(): bool
    {
        return !empty($this->status & static::STATUS_CLONE);
    }


    /** UniOfWork functionalities */

    /**
     * Is managed by EntityManager
     *
     * @return boolean
     */
    public function isContained(): bool
    {
        return $this->wireEntityManager->em->contains($this->entity);
    }

    /**
     * Is in database
     *
     * @return boolean
     */
    public function isPersisted(): bool
    {
        return 
            $this->isContained()
            && ($this->isFlushed() || $this->isLoaded())
            && !$this->isDeleted();
    }

    /**
     * Is entity scheduled for operations
     *
     * @return boolean
     */
    public function isEntityScheduled(): bool
    {
        return $this->uow->isEntityScheduled($this->entity);
    }

    /**
     * Is entity scheduled for dirty check
     *
     * @return boolean
     */
    public function isScheduledForDirtyCheck(): bool
    {
        return $this->uow->isScheduledForDirtyCheck($this->entity);
    }

    /**
     * Is entity scheduled for insert
     *
     * @return boolean
     */
    public function isScheduledForInsert(): bool
    {
        return $this->uow->isScheduledForInsert($this->entity);
    }

    /**
     * Is entity scheduled for update
     *
     * @return boolean
     */
    public function isScheduledForUpdate(): bool
    {
        return $this->uow->isScheduledForUpdate($this->entity);
    }

    public function isScheduledForDelete(): bool
    {
        return $this->uow->isScheduledForDelete($this->entity);
    }


    /** Dispatch requirements */

    /**
     * Dispatch event
     *
     * @param object $event
     * @param string|null $eventName
     * @return object
     */
    protected function dispatch(
        object $event,
        ?string $eventName = null
    ): object
    {
        return $this->wireEntityManager->eventDispatcher->dispatch($event, $eventName);
    }

    /**
     * Apply events
     *
     * @param string|array $eventNames
     * @return static
     */
    public function applyEvents(
        string|array $eventNames
    ): static
    {
        foreach ((array)$eventNames as $eventName) {
            if($this->requireDispatchEvent($eventName)) {
                // Apply events here
                $this->dispatch(new WireEntityEvent($this->entity, $this->wireEntityManager, WireEntityEvent::BEFORE_PERSIST), WireEntityEvent::BEFORE_PERSIST);
                $this->addDispatchedEvent($eventName);
            } else if($this->isDev()) {
                // DEV Control
                throw new Exception(vsprintf('WARN %s line %d: event %s not required!', [__METHOD__, __LINE__, $eventName]));
            }
        }
        return $this;
    }

    /**
     * List of events that can be triggered
     *
     * @return array
     */
    public static function getAvailableEvents(): array
    {
        return [
            WireEntityEvent::ON_EMBED,
            WireEntityEvent::POST_CREATE,
            WireEntityEvent::POST_MODEL,
            WireEntityEvent::POST_CLONE,
            WireEntityEvent::BEFORE_PERSIST,
            WireEntityEvent::BEFORE_UPDATE,
            WireEntityEvent::BEFORE_REMOVE,
            WireEntityEvent::FORM_PRE_SET_DATA,
            WireEntityEvent::FORM_PRE_SUBMIT,
        ];
    }

    /**
     * Is event available to be triggered
     *
     * @param string $eventName
     * @return boolean
     */
    public function isAvailableEvent(
        string $eventName
    ): bool
    {
        $events = static::getAvailableEvents();
        return in_array($eventName, $events) && !$this->isModel();
    }

    /**
     * Get dispatched events as array ($eventName => number of triggers)
     *
     * @return array
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    /**
     * Add event when dispatched/triggered
     *
     * @param string $eventName
     * @param integer $incValue = 1
     * @return static
     */
    public function addDispatchedEvent(
        string $eventName,
        int $incValue = 1
    ): static
    {
        $this->dispatchedEvents[$eventName] ??= 0;
        $this->dispatchedEvents[$eventName] = $this->dispatchedEvents[$eventName] + $incValue;
        if($this->isDev() && $this->dispatchedEvents[$eventName] > $incValue) {
            throw new Exception(vsprintf('Error %s line %d:%s- Event "%s" has been triggered more than once! (exactly %d).', [__METHOD__, __LINE__, PHP_EOL, $eventName, $this->dispatchedEvents[$eventName]]));
        }
        return $this;
    }

    /**
     * Reset dispatched events
     *
     * @param string|array $eventNames = null
     * @return static
     */
    public function resetDispatchedEvents(
        string|array $eventNames = null
    ): static
    {
        if(!empty($eventNames)) {
            $eventNames = (array)$eventNames;
            foreach ($eventNames as $eventName) {
                $this->dispatchedEvents[$eventName] = 0;
            }
        } else {
            $this->dispatchedEvents = [];
        }
        return $this;
    }

    /**
     * Is Event required?
     *
     * @param string $eventName
     * @return boolean
     */
    public function requireDispatchEvent(
        string $eventName
    ): bool
    {
        if($this->isDev() && !$this->isAvailableEvent($eventName)) {
            throw new Exception(vsprintf('Error %s line %d:%s- Event %s is not supported!', [__METHOD__, __LINE__, $eventName]));
        }
        if($this->isEventDispatched($eventName)) return false;
        if($this->isModel()) return false;
        switch ($eventName) {
            case WireEntityEvent::ON_EMBED:
                return true;
                break;
            case WireEntityEvent::BEFORE_PERSIST:
                return !$this->isContained();
                break;
            case WireEntityEvent::BEFORE_UPDATE:
                return
                    !$this->isScheduledForUpdate()
                    && ($this->isScheduledForDirtyCheck())
                    && ($this->isLoaded() || $this->isFlushed())
                    ;
                break;
            case WireEntityEvent::BEFORE_REMOVE:
                return $this->isContained();
                break;
            }
        return false;
    }

    /**
     * Is event dispatched?
     *
     * @param string $eventName
     * @return boolean
     */
    public function isEventDispatched(
        string $eventName
    ): bool
    {
        return array_key_exists($eventName, $this->dispatchedEvents) && $this->dispatchedEvents[$eventName] > 0;
    }


}