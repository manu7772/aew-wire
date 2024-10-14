<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Entity\interface\TraitClonableInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Event\WireEntityEvent;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
// PHP
use Exception;

class EntityEmbededStatus implements EntityEmbededStatusInterface
{

    protected int $typeStatus;
    public readonly WireEntityManagerInterface $wireEntityManager;
    public readonly WireEntityServiceInterface $wireEntityService;

    public function __construct(
        public readonly WireEntityInterface $entity,
        string $type,
        public readonly AppWireServiceInterface $appWire
    )
    {
        $this->wireEntityManager = $this->appWire->get(AppWireServiceInterface::class);
        $this->wireEntityService = $this->wireEntityManager->getEntityService($this->entity);
        $this->entity->setEmbededStatus($this);
        $this->typeStatus = static::ENTITY_STATUS_NULL;
        $this->setType($type);
    }

    protected function failedChangeType(
        string $type
    ): void
    {
        throw new Exception(vsprintf('Error %s line %d: can not change type %s to new type %s!', [__METHOD__, __LINE__, $this->getType(), $type]));
    }

    public function getType(): string
    {
        if($this->isEntity()) return static::TYPE_ENTITY;
        if($this->isModel()) return static::TYPE_MODEL;
        if($this->isCloning() || $this->isCloned()) return static::TYPE_CLONE;
        return static::TYPE_UNDEFINED;
    }

    protected function setType(
        string $type
    ): void
    {
        switch ($type) {
            case static::TYPE_ENTITY_CREATED:
                $this->setCreated();
                break;
            case static::TYPE_ENTITY_LOADED:
                $this->setLoaded();
                break;
            case static::TYPE_MODEL:
                $this->setModel();
                break;
            case static::TYPE_CLONE:
                $this->setCloning();
                break;
            default:
                $this->failedChangeType($type);
                break;
        }
    }

    protected function checkStatus(
        string $status,
        bool $throwException = false
    ): bool
    {
        $main_test = $this->typeStatus & $status;
        $result = static::STATUS_REPEATABLE || $main_test === 0;
        switch ($status) {
            case static::ENTITY_STATUS_NULL:
                $result = false;
                break;
            case static::ENTITY_STATUS_CREATED:
                $result = $result && $this->isEmptyStatus();
                break;
            case static::ENTITY_STATUS_LOADED:
                $result = $result && $this->isEmptyStatus();
                break;
            case static::ENTITY_STATUS_PERSISTED:
                $result = $result
                    && !($this->isDeleted() || $this->isModel() || $this->isLoaded())
                    && ($this->isCreated() || $this->isCloned())
                    ;
                break;
            case static::ENTITY_STATUS_FLUSHING:
                $result = $result
                    && !($this->isCloned() || $this->isModel())
                    ;
                break;
            case static::ENTITY_STATUS_FLUSHED:
                $result = $result && $this->isFlushing();
                break;
            case static::ENTITY_STATUS_TODELETE:
                $result = $result
                    && !($this->isDeleted() || $this->isModel())
                    && (!($this->isCreated() || $this->isCloned()) || $this->isFlushed())
                    ;
                break;
            case static::ENTITY_STATUS_DELETED:
                $result = $result
                    && $this->isTodelete()
                    && ((!$this->isCreated() && !$this->isCloned()) || $this->isFlushed())
                    && !$this->isModel()
                    ;
                break;
            case static::ENTITY_STATUS_MODEL:
                $result = $result && $this->isEmptyStatus();
                break;
            case static::ENTITY_STATUS_CLONED:
                $result = $result && $this->isEmptyStatus();
                if(!$this->isClonable()) $result = false;
                break;
            case static::ENTITY_STATUS_CLONING:
                // $result = $result && $this->isCloned();
                if(!$this->isClonable()) $result = false;
                break;
            default:
                $result = false;
                break;
        }
        if(!$result && $throwException) {
            $this->failedChangeType($status);
        }
        return $result;
    }

    public function isEmptyStatus(): bool
    {
        return $this->typeStatus === 0;
    }


    /** Dispatch requirements */

    public function requireDispatchEvent(
        string $eventName
    ): bool
    {
        switch ($eventName) {
            case WireEntityEvent::BEFORE_REMOVE:
                return ($this->isLoaded() || $this->isFlushed())
                    && !$this->isModel()
                    ;
                break;
            case WireEntityEvent::BEFORE_UPDATE:
                return ($this->isLoaded() || $this->isFlushed())
                    && !$this->isModel()
                    ;
                break;
            case WireEntityEvent::BEFORE_PERSIST:
                return !$this->isLoaded()
                    && !$this->isPersisted()
                    && !$this->isFlushed()
                    && !$this->isModel()
                    ;
                break;
            default:
                if($this->appWire->isDev()) {
                    throw new Exception(vsprintf('Error %s line %d: Event %s is not supported!', [__METHOD__, __LINE__, $eventName]));
                }
                break;
        }
        return false;
    }

    /** ENTITY */

    public function isEntity(): bool
    {
        $test = $this->typeStatus & (static::ENTITY_STATUS_CREATED | static::ENTITY_STATUS_LOADED);
        return $test > 0;
    }

    public function setCreated(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_CREATED, true);
        $this->typeStatus = static::ENTITY_STATUS_CREATED;
        return $this;
    }

    public function isCreated(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_CREATED;
        return $test > 0;
    }

    public function setLoaded(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_LOADED, true);
        $this->typeStatus = static::ENTITY_STATUS_LOADED;
        return $this;
    }

    public function isLoaded(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_LOADED;
        return $test > 0;
    }

    public function setPersisted(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_PERSISTED, true);
        $this->typeStatus = $this->typeStatus | static::ENTITY_STATUS_PERSISTED;
        return $this;
    }

    public function isPersisted(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_PERSISTED;
        return $test > 0;
    }

    public function setFlushed(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_FLUSHING, true);
        $this->typeStatus = $this->typeStatus ^ static::ENTITY_STATUS_FLUSHED; // Remove postflushed if is
        $this->typeStatus = $this->typeStatus | static::ENTITY_STATUS_FLUSHING;
        return $this;
    }

    public function isFlushing(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_FLUSHING;
        return $test > 0;
    }

    public function setPostflushed(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_FLUSHED, true);
        $this->typeStatus = $this->typeStatus ^ static::ENTITY_STATUS_FLUSHING; // Remove flushed
        $this->typeStatus = $this->typeStatus | static::ENTITY_STATUS_FLUSHED;
        return $this;
    }

    public function isFlushed(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_FLUSHED;
        return $test > 0;
    }

    public function setTodeleted(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_TODELETE, true);
        $this->typeStatus = $this->typeStatus | static::ENTITY_STATUS_TODELETE;
        return $this;
    }

    public function isTodelete(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_TODELETE;
        return $test > 0;
    }

    public function setDeleted(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_DELETED, true);
        $this->typeStatus = $this->typeStatus | static::ENTITY_STATUS_DELETED;
        return $this;
    }

    public function isDeleted(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_DELETED;
        return $test > 0;
    }


    /** MODEL */

    public function setModel(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_MODEL, true);
        $this->typeStatus = static::ENTITY_STATUS_MODEL;
        return $this;
    }

    public function isModel(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_MODEL;
        return $test > 0;
    }


    /** CLONE */

    public function isClonable(): bool
    {
        return $this->entity instanceof TraitClonableInterface && $this->entity::IS_CLONABLE;
    }

    public function setCloning(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_CLONING, true);
        $this->typeStatus = $this->typeStatus | static::ENTITY_STATUS_CLONING;
        return $this;
    }

    public function isCloning(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_CLONING;
        return $test > 0;
    }

    public function setCloned(): static
    {
        if($this->appWire->isDev()) $this->checkStatus(static::ENTITY_STATUS_CLONED, true);
        $this->typeStatus = static::ENTITY_STATUS_CLONED;
        return $this->setCreated(); // --> becomes ENTITY
    }

    public function isCloned(): bool
    {
        $test = $this->typeStatus & static::ENTITY_STATUS_CLONED;
        return $test > 0;
    }


}