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
Class AdminGroup extends BaseClassAttribute implements AppAttributeClassInterface
{

    public function __construct(
        public string $group,
        public int $order = 0,
        public ?string $icon = null,
    ) {}

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $data = [
            'group' => $this->group,
            'order' => $this->order,
            'icon' => $this->icon,
        ];
        return array_merge($parent, $data);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->group = $data['group'];
        $this->order = $data['order'];
        $this->icon = $data['icon'];
    }

}
