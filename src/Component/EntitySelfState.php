<?php

namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Component\interface\EntitySelfStateInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Tools\Encoders;
// PHP
use Exception;

class EntitySelfState implements EntitySelfStateInterface
{
    private int $state = 0b00000000;
    private int $event = 0b00000000;

    public function __construct(
        private readonly BaseEntityInterface $entity,
        string $initial_state,
        private bool|string $debug = 'auto'
    ) {
        $this->state = static::STATES[$initial_state];
    }

    public function getReport(
        bool $asString = false
    ): array|string
    {
        $report = [
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

    public function isDebug(): bool
    {
        if (is_bool($this->debug)) {
            return $this->debug;
        }
        $embs = $this->entity->getEmbededStatus();
        return $embs instanceof EntityEmbededStatusInterface ? $embs->isDev() : false;
    }

    public function setNew(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('new');
        $this->state = static::STATES['new'];
        return $this;
    }

    public function isNew(): bool
    {
        return ($this->state & static::STATES['new']) > 0;
    }

    public function setLoaded(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('loaded');
        $this->state = static::STATES['loaded'];
        return $this;
    }

    public function isLoaded(): bool
    {
        return ($this->state & static::STATES['loaded']) > 0;
    }

    public function setPersisted(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('persisted');
        $this->state = $this->state | static::STATES['persisted'];
        return $this;
    }

    public function isPersisted(): bool
    {
        return ($this->state & static::STATES['persisted']) > 0;
    }

    public function setUpdated(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('updated');
        $this->state = $this->state | static::STATES['updated'];
        return $this;
    }

    public function isUpdated(): bool
    {
        return ($this->state & static::STATES['updated']) > 0;
    }

    public function setRemoved(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('removed');
        $this->state = $this->state | static::STATES['removed'];
        return $this;
    }

    public function isRemoved(): bool
    {
        return ($this->state & static::STATES['removed']) > 0;
    }

    public function setDetached(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('detached');
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
        if ($this->isDebug()) $this->exceptionOnInvalidState('model');
        $this->state = static::STATES['model'];
        return $this;
    }

    public function isModel(): bool
    {
        $embs = $this->entity->getEmbededStatus();
        return $embs instanceof EntityEmbededStatusInterface
            ? $embs->isModel()
            : ($this->state & static::STATES['model']) > 0;
    }


    private function isValidNewState(
        string $state
    ): bool {
        switch ($state) {
            case 'model':
                return empty($this->state);
                break;
            case 'new':
                return empty($this->state);
                break;
            case 'loaded':
                return empty($this->state);
                break;
            case 'persisted':
                return $this->isNew() && !$this->isRemoved() && !$this->isModel();
                break;
            case 'updated':
                return ($this->isLoaded() || $this->isPersisted()) && !$this->isRemoved() && !$this->isModel();
                break;
            case 'removed':
                return ((0b00000111 & $this->state) > 0) && !$this->isModel();
                break;
            case 'detached':
                return !$this->isModel();
                break;
            // Entity Events
            case 'event_post_created':
                return $this->event === 0b00000000;
                break;
            case 'event_post_loaded':
                return $this->event === 0b00000000;
                break;
            case 'event_post_persisted':
                return $this->isPostCreated() && !$this->isPostLoaded();
                break;
            case 'event_post_updated':
                return $this->isPostLoaded() && !$this->isPostCreated();
                break;
            default:
                return false;
                break;
        }
    }

    private function exceptionOnInvalidState(
        string $state
    ): void {
        if (!$this->isValidNewState($state)) {
            $addEntity = '';
            if($this->entity instanceof UnameInterface) {
                $owner = $this->entity->getEntity();
                $addEntity = vsprintf('%s>>> INFO: this UnameInterface owner is %s %s (id: %s):%s%s', [PHP_EOL, $owner->getClassname(), $owner, $owner->getId(), PHP_EOL, $owner->getSelfState()->getReport(true)]);
                dump($this->entity, $owner);
            }
            throw new Exception(vsprintf('Error %s line %d: added state %s is invalid for entity %s %s (id: %s):%s%s%s', [__METHOD__, __LINE__, $state, $this->entity->getClassname(), $this->entity, $this->entity->getId(), PHP_EOL, $this->entity->getSelfState()->getReport(true), $addEntity]));
        }
    }


    /**
     * EVENTS
     * 
     */

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
        if ($this->isDebug()) $this->exceptionOnInvalidState('event_post_created');
        $this->event = $this->event | static::POST_CREATED;
        return $this;
    }

    public function isPostCreated(): bool
    {
        return ($this->event & static::POST_CREATED) > 0;
    }

    public function setPostLoaded(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('event_post_loaded');
        $this->event = $this->event | static::POST_LOADED;
        return $this;
    }

    public function isPostLoaded(): bool
    {
        return ($this->event & static::POST_LOADED) > 0;
    }

    public function setPostPersisted(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('event_post_persisted');
        $this->event = $this->event | static::POST_PERSISTED;
        return $this;
    }

    public function isPostPersisted(): bool
    {
        return ($this->event & static::POST_PERSISTED) > 0;
    }

    public function setPostUpdated(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('event_post_updated');
        $this->event = $this->event | static::POST_UPDATED;
        return $this;
    }

    public function isPostUpdated(): bool
    {
        return ($this->event & static::POST_UPDATED) > 0;
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