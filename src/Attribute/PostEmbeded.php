<?php
namespace Aequation\WireBundle\Attribute;

// PHP
use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_METHOD)]
class PostEmbeded extends BaseMethodAttribute
{
    public const ON_VALUES = ['create','load'];

    public function __construct(
        public null|string|array $on = null,
        public array $arguments = [],
    ) {
        if(empty($this->on)) $this->on = static::ON_VALUES;
        $this->on = (array)$this->on;
        foreach ($this->on as $on) {
            if(!in_array($on, static::ON_VALUES)) throw new Exception(vsprintf('Error %s line %d: on must set in these values : %s', [__METHOD__, __LINE__, json_encode(static::ON_VALUES)]));
        }
    }

    public function isOnCreate() 
    {
        return in_array('create', $this->on);
    }

    public function isOnLoad() 
    {
        return in_array('load', $this->on);
    }

    public function getMethodArguments(): array
    {
        return $this->arguments;
    }

}