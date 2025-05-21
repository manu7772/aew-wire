<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Service\CacheService;
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
// PHP
use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class CacheManaged extends BaseMethodAttribute
{
    public mixed $data = null;
    public string $serviceId;

    public function __construct(
        public string $name,
        public array $params = [],
        public ?string $commentaire = null,
    ) {
        if(!CacheService::isKeyvalid($this->name)) {
            throw new Exception(vsprintf('Error %s line %d: name %s is invalid, must be a valid key for cache service', [__METHOD__, __LINE__, $this->name]));
        }
    }

    public function getClassObject(): ?object
    {
        return $this->object ?? null;
    }

    public function loadCacheData(
        CacheServiceInterface $cacheService,
        bool $reset = false
    ): bool
    {
        if($this->method->isPublic()) {
            if($reset) {
                $cacheService->delete($this->name);
            }
            $this->data = $cacheService->get(
                $this->name,
                function() {
                    $result = $this->method->invokeArgs($this->getClassObject(), $this->params);
                    if($result instanceof OpresultInterface) {
                        $result = $result->isSuccess() ? $result->getData() : false;
                    }
                    return $result;
                },
                $this->commentaire
            );
            // dump($this->name, $this->data);
            $result = $this->data !== false;
        }
        return $result ?? false;
    }

    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): static
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $parent['name'] = $this->name;
        $parent['params'] = $this->params;
        $parent['commentaire'] = $this->commentaire;
        $parent['serviceId'] = $this->serviceId;
        return $parent;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->name = $data['name'];
        $this->params = $data['params'] ?? [];
        $this->commentaire = $data['commentaire'] ?? null;
        $this->serviceId = $data['serviceId'];
        $this->setObject($data['object']);
    }

}
