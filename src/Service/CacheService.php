<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Attribute\CacheManaged;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
use Aequation\WireBundle\Service\interface\WireServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
// PHP
use Exception;

#[AsAlias(CacheServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class CacheService implements CacheServiceInterface
{
    use TraitBaseService;

    public const SERVICE_CACHES_NAMES = 'caches_names';
    public const FORBIDDEN_KEYS = [
        'caches_names',
        'cache',
        'cache_dir',
        'cache_dir_path',
        'cache_dir_url',
    ];

    public function __construct(
        public readonly KernelInterface $kernel,
        protected readonly LoggerInterface $logger,
        protected CacheInterface $cache,
        // #[Autowire(param: 'kernel.cache_dir')]
        // public string $cacheDir,
    )
    {
        // Constructor logic here
    }


    /****************************************************************************************************************************
     * KEYS
     ****************************************************************************************************************************/

    public static function isKeyvalid(string $key): bool
    {
        return static::isInternalKeyValid($key) && !in_array($key, static::FORBIDDEN_KEYS);
    }

    protected static function isInternalKeyValid(string $key): bool
    {
        return preg_match('/^[a-zA-Z0-9_\.]+$/', $key);
    }

    protected function checkValidKey(string $key): void
    {
        if(!static::isInternalKeyValid($key)) {
            throw new Exception(vsprintf('Error %s line %d: key "%s" is invalid, must be a valid key for cache service', [__METHOD__, __LINE__, $key]));
        }
    }

    /****************************************************************************************************************************
     * INTERNAL INFO
     ****************************************************************************************************************************/

    // #[CacheManaged(name: 'cache_managed_names', commentaire: 'Get the list of cache managed names')]
    public function getCacheables(
        bool $reset = false
    ): array
    {
        $attributes = $this->get(($reset ? '!' : '').static::SERVICE_CACHES_NAMES, fn() => array_map(fn($attribute) => $attribute->jsonSerialize(), $this->getCacheableAttributes()));
        foreach ($attributes as $key => $attrValues) {
            $starter = array_filter($attrValues, fn($value) => in_array($value, ['name', 'params', 'commentaire']), ARRAY_FILTER_USE_KEY);
            $cm = new CacheManaged(...$starter);
            $attrValues['object'] = $this->kernel->getContainer()->get($attrValues['serviceId'], ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE);
            $cm->jsonUnserialize($attrValues);
            $attributes[$key] = $cm;
        }
        return $attributes;
    }

    private function getCacheableAttributes(): array
    {
        $caches = [];
        /** @var ContainerInterface $container */
        // $container = $this->kernel->getContainer();
        foreach (get_declared_interfaces() as $interface) {
            if(is_a($interface, WireServiceInterface::class, true) && $this->kernel->getContainer()->has($interface)) {
            // if(preg_match('/^(Aequation\\\\WireBundle\\\\|App\\\\)/', $interface)) {
                if($service = $this->kernel->getContainer()->get($interface, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)) {
                    /** @var WireServiceInterface $service */
                    $attributes_list = Objects::getMethodAttributes($service, CacheManaged::class);
                    foreach ($attributes_list as $attributes) {
                        foreach ($attributes as $attribute) {
                            if($attribute instanceof CacheManaged) {
                                if(in_array($attribute->name, $caches)) {
                                    throw new Exception(vsprintf('Error %s line %d: name "%s" is used twice (or more) in %s method "%s"', [__METHOD__, __LINE__, $attribute->name, $service->getName(), $attribute->getMethodName()]));
                                }
                                if(!$attribute->method->isPublic()) {
                                    throw new Exception(vsprintf('Error %s line %d: name "%s" is used in a non public method "%s" in %s', [__METHOD__, __LINE__, $attribute->name, $attribute->getMethodName(), $service->getName()]));
                                }
                                $attribute->setServiceId($interface);
                                $caches[$attribute->name] = $attribute;
                            }
                        }
                    }
                } else if($this->kernel->getContainer()->has($interface)) {
                    $this->logger->warning(vsprintf('Service "%s" could not be loaded for getting %s attributes', [$interface, CacheManaged::class]));
                }
            }
        }
        return $caches;
    }


    /****************************************************************************************************************************
     * UTILITIES
     ****************************************************************************************************************************/

    public function get(
        string $key,
        callable $callback,
        ?string $commentaire = null,
        ?float $beta = null,
        ?array &$metadata = null
    ): mixed
    {
        if(preg_match('/^!/', $key)) {
            $key = ltrim($key, '!');
            return $this->reset($key, $callback, $commentaire, $beta, $metadata);
        }
        $this->checkValidKey($key);
        return $this->cache->get($key, $callback, $beta, $metadata);
    }

    public function reset(
        string $key,
        callable $callback,
        ?string $commentaire = null,
        ?float $beta = null,
        ?array &$metadata = null
    ): mixed
    {
        if($this->delete($key)) {
            return $this->get($key, $callback, $commentaire, $beta, $metadata);
        }
        return false;
    }

    public function delete(
        string $key
    ): bool
    {
        $this->checkValidKey($key);
        return $this->cache->delete($key);
    }

    public function deleteAll(): bool
    {
        $result = true;
        foreach ($this->getKeys() as $key) {
            $result = $result && $this->delete($key);
        }
        return $result;
    }

    public function getKeys(): array
    {
        $caches = $this->getCacheables();
        return array_keys($caches);
    }

    public function hasCache(string $key): bool
    {
        $caches = $this->getKeys();
        return array_key_exists($key, $caches);
    }

}