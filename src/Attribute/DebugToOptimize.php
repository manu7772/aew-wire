<?php
namespace Aequation\WireBundle\Attribute;

// PHP
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class DebugToOptimize extends BaseMethodAttribute
{

    public const TYPES = [
        'info' => 'Notification',
        'warning' => 'Important',
        'urgent' => 'Urgent',
    ];

    public function __construct(
        private string $type,
        private string $description,
    ) {
        $types = static::getTypes();
        if (!in_array($this->type, $types)) {
            $this->type = array_key_first(static::TYPES);
        }
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeIcon(): array
    {
        return match ($this->type) {
            'info' => [
                'name' => 'tabler:info-circle',
                'class' => 'w-6 h-6 text-emerald-500 inline-block',
            ],
            'warning' => [
                'name' => 'tabler:exclamation-circle',
                'class' => 'w-6 h-6 text-orange-400 inline-block',
            ],
            'urgent' => [
                'name' => 'tabler:exclamation-circle-filled',
                'class' => 'w-6 h-6 text-red-500 inline-block',
            ],
        };
    }

    public function getTypeLabel(): string
    {
        return static::TYPES[$this->type];
    }

    #[CssClasses(target: 'value')]
    public function getUsedCssClasses(): array
    {
        $css = [];
        foreach ($this->getTypeIcon() as $value) {
            $cls = preg_split('/\s+/', $value['class']);
            foreach ($cls as $c) {
                $css[$c] = $c;
            }
        }
        return array_values($css);
    }

    public static function getTypes(): array
    {
        return array_keys(static::TYPES);
    }

}