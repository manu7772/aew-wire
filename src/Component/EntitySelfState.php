<?php

namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Component\interface\EntityEmbededStatusInterface;
use Aequation\WireBundle\Tools\Encoders;

class EntitySelfState
{
    public const STATES = [
        'new'       => 0b00000001,
        'loaded'    => 0b00000010,
        'persisted' => 0b00000100,
        'updated'   => 0b00001000,
        'removed'   => 0b00010000,
        'detached'  => 0b00100000,
        'model'     => 0b01000000,
    ];

    private int $state = 0b00000000;

    public function __construct(
        private readonly WireEntityInterface $entity,
        string $initial_state,
        private bool|string $debug = 'auto'
    ) {
        // if(!isset($this->entity->__selfstate)) $this->entity->__selfstate = $this;
        $this->state = static::STATES[$initial_state];
    }

    public function isDebug(): bool
    {
        if (is_bool($this->debug)) {
            return $this->debug;
        }
        $embs = $this->entity->getEmbededStatus();
        return $embs instanceof EntityEmbededStatusInterface ? $embs->isDev() : false;
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
            : $this->state & static::STATES['model'] > 0;
    }

    public function setNew(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('new');
        $this->state = static::STATES['new'];
        return $this;
    }

    public function isNew(): bool
    {
        return $this->state & static::STATES['new'] > 0;
    }

    public function setLoaded(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('loaded');
        $this->state = static::STATES['loaded'];
        return $this;
    }

    public function isLoaded(): bool
    {
        return $this->state & static::STATES['loaded'] > 0;
    }

    public function setPersisted(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('persisted');
        $this->state = $this->state | static::STATES['persisted'];
        return $this;
    }

    public function isPersisted(): bool
    {
        return $this->state & static::STATES['persisted'] > 0;
    }

    public function setUpdated(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('updated');
        $this->state = $this->state | static::STATES['updated'];
        return $this;
    }

    public function isUpdated(): bool
    {
        return $this->state & static::STATES['updated'] > 0;
    }

    public function setRemoved(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('removed');
        $this->state = $this->state | static::STATES['removed'];
        return $this;
    }

    public function isRemoved(): bool
    {
        return $this->state & static::STATES['removed'] > 0;
    }

    public function setDetached(): static
    {
        if ($this->isDebug()) $this->exceptionOnInvalidState('detached');
        $this->state = $this->state | static::STATES['detached'];
        return $this;
    }

    public function isDetached(): bool
    {
        return $this->state & static::STATES['detached'] > 0;
    }


    private function isValidNewState(
        string $state
    ): bool {
        switch ($state) {
            case 'model':
                return empty($this->state) && empty($this->entity->getId());
                break;
            case 'new':
                return empty($this->state) && empty($this->entity->getId());
                break;
            case 'loaded':
                return empty($this->state) && !empty($this->entity->getId());
                break;
            case 'persisted':
                return $this->isNew() && !$this->isRemoved() && !$this->isModel() && !empty($this->entity->getId());
                break;
            case 'updated':
                return $this->isLoaded() && !$this->isRemoved() && !$this->isModel() && !empty($this->entity->getId());
                break;
            case 'removed':
                return (0b00000111 & $this->state > 0) && !$this->isModel() && !empty($this->entity->getId());
                break;
            case 'detached':
                return true;
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
            throw new Exception(vsprintf('Error %s line %d: added state %s is invalid for entity %s %s.%sCurrent state is %s.', [__METHOD__, __LINE__, $state, $this->entity->getClassname(), $this->entity->__toString(), Encoders::toBin($this->state)]));
        }
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