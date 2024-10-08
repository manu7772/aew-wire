<?php
namespace Aequation\WireBundle\Attribute;

use Aequation\WireBundle\Attribute\interface\AppAttributeClassInterface;
// PHP
use Attribute;

/**
 * Service for object
 * @Target({"CLASS"})
 * @author emmanuel:dujardin Aequation
 */
#[Attribute(Attribute::TARGET_CLASS)]
Class ClassCustomService extends baseClassAttribute implements AppAttributeClassInterface
{

    public function __construct(
        public string $service,
    ) {}

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $data = [
            'service' => $this->service,
        ];
        return array_merge($parent, $data);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->service = $data['service'];
    }

}