<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Attribute\PostEmbeded;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Events;
// PHP
use BadMethodCallException;
use Exception;
use ReflectionMethod;

/**
 * EntitySelfState
 * Entity status container of useful methods
 * 
 * Binary operations eg.:
 * @see https://onlinephp.io/c/29621
 * and a end of this file
 */
class EntitySelfState implements EntitySelfStateInterface
{
    private int $state = 0b00000000;
    private int $event = 0b00000000;
    public readonly EntityEmbededStatusInterface $embededStatus;
    public readonly AppWireServiceInterface $appWire;

    public function __construct(
        public readonly BaseEntityInterface $entity
     ) {
        $contructor_used = $this->entity->__selfstate_constructor_used ?? false;
        switch (true) {
            case $contructor_used:
                $this->setNew();
                break;
            default:
                $this->setLoaded();
                break;
        }
    }

    public function isReady(): bool
    {
        return isset($this->appWire);
    }

    public function isStarted(): bool
    {
        return isset($this->embededStatus);
    }
    
    /**
     * Start embeded status
     * 
     * @param AppWireServiceInterface|null $appWire
     * @throws BadMethodCallException
     */
    public function startEmbed(
        AppWireServiceInterface $appWire,
        bool $startNow = false
    ): bool
    {
        $this->appWire ??= $appWire;
        return $startNow && !($this->embededStatus instanceof EntityEmbededStatusInterface)
            ? $this->internalStartEmbed()
            : $this->isStarted();
    }

    private function internalStartEmbed(): bool
    {
        if(!$this->isStarted()) {
            if(!isset($this->appWire)) {
                throw new BadMethodCallException(vsprintf('Error %s line %d: cant not start EmbededStatus, because appWire service is not set!', [__METHOD__, __LINE__]));
            }
            $this->embededStatus = new EntityEmbededStatus($this, $this->appWire);
        }
        return $this->isStarted();
    }

    public function getEmbededStatus(): ?EntityEmbededStatusInterface
    {
        return isset($this->embededStatus) || $this->internalStartEmbed() ? $this->embededStatus : null;
    }


    /*******************************************************************************************
     * MAGIC METHODS on EntityEmbededStatus
     */

    /**
     * Call method on embeded status
     * 
     * @param string $name
     * @param array $arguments
     * @throws BadMethodCallException
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (($this->isStarted() || $this->internalStartEmbed()) && method_exists($this->embededStatus, $name)) {
            return $this->embededStatus->$name(...$arguments);
        }
        throw new BadMethodCallException(vsprintf('Error %s line %d: method %s not found! Maybe startEmbed() method needs to be used before?', [__METHOD__, __LINE__, $name]));
    }

    public function __isset(string $name)
    {
        return ($this->isStarted() || $this->internalStartEmbed()) && isset($this->embededStatus->$name);
    }

    public function __get(string $name)
    {
        if ($this->__isset($name)) {
            return $this->embededStatus->$name;
        }
        throw new BadMethodCallException(vsprintf('Error %s line %d: property %s not found! Maybe startEmbed() method needs to be used before?', [__METHOD__, __LINE__, $name]));
    }
    
    
    /*******************************************************************************************
     * STATES
     */

    public function isExactBinState(int $state): bool
    {
        return $this->state === $state;
    }

    public function isExactState(string $state): bool
    {
        return $this->state === static::STATES[$state];
    }

    private function setNew(): static
    {
        $this->state = static::STATES['new'];
        return $this;
    }

    public function isNew(): bool
    {
        return ($this->state & static::STATES['new']) > 0;
    }

    private function setLoaded(): static
    {
        $this->state = static::STATES['loaded'];
        return $this;
    }

    public function isLoaded(): bool
    {
        return ($this->state & static::STATES['loaded']) > 0;
    }

    public function setPersisted(): static
    {
        $this->state = $this->state | static::STATES['persisted'];
        return $this;
    }

    public function isPersisted(): bool
    {
        return ($this->state & static::STATES['persisted']) > 0;
    }

    public function setUpdated(): static
    {
        $this->state = $this->state | static::STATES['updated'];
        return $this;
    }

    public function isUpdated(): bool
    {
        return ($this->state & static::STATES['updated']) > 0;
    }

    public function setRemoved(): static
    {
        $this->state = $this->state | static::STATES['removed'];
        return $this;
    }

    public function isRemoved(): bool
    {
        return ($this->state & static::STATES['removed']) > 0;
    }

    public function setDetached(): static
    {
        $this->state = $this->state | static::STATES['detached'];
        return $this;
    }

    public function isDetached(): bool
    {
        return ($this->state & static::STATES['detached']) > 0;
    }

    public function isEntity(): bool
    {
        return !$this->isModel();
    }

    public function setModel(): static
    {
        $this->state = $this->state | static::STATES['model'];
        return $this;
    }

    public function isModel(): bool
    {
        return ($this->state & static::STATES['model']) > 0;
    }


    /*******************************************************************************************
     * EVENTS
     */

    public function applyEvents(): void
    {
        $attributes = Objects::getMethodAttributes($this->entity, PostEmbeded::class, ReflectionMethod::IS_PUBLIC);
        switch (true) {
            case $this->isNew():
                // Starter start
                // $this->startEmbed();
                if(!$this->isPostCreated()) {
                    foreach ($attributes as $instances) {
                        /** @var PostEmbeded $instance */
                        $instance = reset($instances);
                        if($instance->isOnCreate()) {
                            $this->entity->{$instance->getMethodName()}(...$instance->getMethodArguments());
                        }
                    }
                    $this->setPostCreated();
                }
                break;
            case $this->isLoaded():
                // Starter start
                // $this->startEmbed();
                if(!$this->isPostLoaded()) {
                    foreach ($attributes as $instances) {
                        /** @var PostEmbeded $instance */
                        $instance = reset($instances);
                        if($instance->isOnLoad()) {
                            $this->entity->{$instance->getMethodName()}(...$instance->getMethodArguments());
                        }
                    }
                    $this->setPostLoaded();
                }
                break;
            default:
                throw new Exception(vsprintf('Error %s line %d: the entity status is neither new nor loaded.', [__METHOD__, __LINE__]));
                break;
        }
    }
 
    /**
     * Is event done
     * $bin is a binary value (eg. 0b00000001) or integer (eg. 1)
     */
    public function eventDone(
        string|int $bin
    ): bool
    {
        return $this->event & $bin > 0;
    }

    public function setPostCreated(): static
    {
        $this->event = $this->event | static::POST_CREATED;
        return $this;
    }

    public function isPostCreated(): bool
    {
        return ($this->event & static::POST_CREATED) > 0;
    }

    public function setPostLoaded(): static
    {
        $this->event = $this->event | static::POST_LOADED;
        return $this;
    }

    public function isPostLoaded(): bool
    {
        return ($this->event & static::POST_LOADED) > 0;
    }

    public function setPostPersisted(): static
    {
        $this->event = $this->event | static::POST_PERSISTED;
        return $this;
    }

    public function isPostPersisted(): bool
    {
        return ($this->event & static::POST_PERSISTED) > 0;
    }

    public function setPostUpdated(): static
    {
        $this->event = $this->event | static::POST_UPDATED;
        return $this;
    }

    public function isPostUpdated(): bool
    {
        return ($this->event & static::POST_UPDATED) > 0;
    }


    /*******************************************************************************************
     * REPORT
     */

    public function getReport(bool $asString = false): array|string
    {
        $report = [
            // entity
            '_entity' => $this->entity->getClassname().'::'.$this->entity,
            '_constructor_used' => $this->entity->__selfstate_constructor_used ?? false,
            // state
            '_state' => Encoders::toBin($this->state),
            'state_new'       => $this->isNew(),
            'state_loaded'    => $this->isLoaded(),
            'state_persisted' => $this->isPersisted(),
            'state_updated'   => $this->isUpdated(),
            'state_removed'   => $this->isRemoved(),
            'state_detached'  => $this->isDetached(),
            'state_model'     => $this->isModel(),
            // event
            '_event' => Encoders::toBin($this->event),
            'event_post_created' => $this->isPostCreated(),
            'event_post_loaded' => $this->isPostLoaded(),
            'event_post_persisted' => $this->isPostPersisted(),
            'event_post_updated' => $this->isPostUpdated(),
        ];
        if($asString) {
            $strings = [];
            foreach ($report as $key => $value) {
                if(is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else if(is_int($value)) {
                    $value = '(bin) '.Encoders::toBin($value);
                }
                $strings[] = vsprintf('- %s => %s', [$key, $value]);
            }
            return implode(PHP_EOL, $strings);
        }
        return $report;
    }

}


/**
 * @see https://onlinephp.io/c/29621
 */

/*

$test = 13;
$combine = 1;

$result1 = $test & $combine;
$result2 = $test | $combine;
$result3 = $test ^ $combine;

function toBin(int $num) {
	return str_pad(decbin($num), 8, 0, STR_PAD_LEFT);
}

echo("Test 1 => ".toBin($test)." & ".toBin($combine)." : $result1 (".toBin($result1).")".PHP_EOL);
echo("Test 2 => ".toBin($test)." | ".toBin($combine)." : $result2 (".toBin($result2).")".PHP_EOL);
echo("Test 3 => ".toBin($test)." ^ ".toBin($combine)." : $result3 (".toBin($result3).")".PHP_EOL);

*/