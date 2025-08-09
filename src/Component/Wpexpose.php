<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Component\interface\WpexposeInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Objects;
use InvalidArgumentException;

class Wpexpose implements WpexposeInterface
{

    public const DEFAULT_ELEMENTS = [
        'interface' => null,
        'plural' => false,
    ];

    protected array $elements;

    public function __construct(...$params)
    {
        $this->elements = [];
        foreach (static::transformParams($params) as $key => $value) {
            $setter = 'set'.ucfirst($key);
            $this->$setter($value);
        }
    }

    protected static function transformParams($params): array
    {
        // dump($params);
        if(empty($params)) {
            return [];
        }
        $first = reset($params);
        if(is_object($first)) {
            if($first instanceof WpexposeInterface && !$first->isValid()) {
                $message = vsprintf('Error %s line %d: Wpexpose expects an array or a JSON string, got [%s] "%s"', [__METHOD__, __LINE__, gettype($first), Objects::toDebugString($first)]);
                throw new InvalidArgumentException($message);
                // dump($message);
            }
        }
        if(Encoders::isJson($first)) {
            $params = Encoders::fromJson($first, true);
        } else if(is_array($first)) {
            $params = $first;
        } else {
            // $params = $params;
        }
        $keys = array_keys(static::DEFAULT_ELEMENTS);
        if(array_is_list($params)) {
            if(count($params) !== count($keys)) {
                dump($params);
                $message = vsprintf('Error %s line %d: Wpexpose expects an array with keys %s, got %s', [__METHOD__, __LINE__, json_encode($keys), json_encode($params)]);
                throw new InvalidArgumentException($message);
            }
            $params = array_combine($keys, $params);
        }
        // dump($params, $keys);
        $params = array_merge(static::DEFAULT_ELEMENTS, array_filter($params, fn ($key) => in_array($key, $keys), ARRAY_FILTER_USE_KEY));
        // dump($params);
        return $params;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if(!$this->isValid()) {
            return 'INVALID';
        }
        return ($this->getInterface() ?? 'EMPTY').($this->isPlural() ? '[]' : '');
    }

    public static function replaceHashes(string $string): string
    {
        return preg_replace('#(\\\\{2,})#', '\\', $string);
    }

    public function __toRepresentation(): string
    {
        return $this->__toString();
    }

    public function toArray(): array
    {
        return $this->elements;
    }

    public function isValid(): bool
    {
        return !empty($this->getInterface());
    }

    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function getInterface(): ?string
    {
        return $this->elements['interface'];
    }

    public function setInterface(string $interface): static
    {
        $this->elements['interface'] = static::replaceHashes($interface);
        return $this;
    }

    public function isPlural(): bool
    {
        return $this->elements['plural'];
    }

    public function setPlural(bool $plural = true): static
    {
        $this->elements['plural'] = $plural;
        return $this;
    }

    public function setSingular(bool $singlular = true): static
    {
        $this->elements['plural'] = !$singlular;
        return $this;
    }

    public function isSingular(): bool
    {
        return !$this->isPlural();
    }


    /**
     * Check if the item is an interface of the given interface(s).
     * If plural is null, it will return true if the item is singular or plural.
     *
     * @param string|array|object $interfaces
     * @param bool|null $plural
     * @return bool
     */
    public function isInterfaceOf(string|array|object $interfaces, ?bool $plural = null): bool
    {
        $interfaces = array_map(fn ($if) => is_object($if) ? Objects::getClassname($if) : $if, is_array($interfaces) ? $interfaces : [$interfaces]);
        foreach ($interfaces as $if) {
            if($this->matchParams($if, $plural)) {
                return true;
            }
        }
        return false;
    }

    public function matchParams(...$params): bool
    {
        // dump($params);
        if(reset($params) instanceof WpexposeInterface) {
            $params = reset($params);
        }
        foreach (static::transformParams($params) as $name => $value) {
            if(!$this->matchParam($name, $value)) {
                return false;
            }
        }
        // dump($params);
        return true;
    }

    public function matchParam(string $param_name, mixed $value): bool
    {
        if(!$this->isValid()) {
            return false;
        }
        switch ($param_name) {
            case 'interface':
                // dump($value.' is a '.$this->elements['interface'].' ? '.json_encode(is_a($value, $this->elements['interface'], true)));
                return is_a($value, $this->elements['interface'], true);
                break;
            case 'plural':
                return is_null($value) || $this->elements['plural'] === $value;
                break;
            default:
                throw new InvalidArgumentException(sprintf('Error %s line %d: unknown parameter "%s" for Wpexpose', __METHOD__, __LINE__, $param_name));
                break;
        }
    }

}