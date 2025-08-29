<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Interface\ClassDescriptionInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Iterables;
use Aequation\WireBundle\Tools\Objects;
use InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent(
    name: 'wire:button',
    template: '@AequationWire/components/button.html.twig'
)]
class Button
{
    const TYPES = [
        'button' => 'btn btn-accent',
        'ghost' => 'btn btn-ghost',
        'link' => '', // btn btn-link -- does not work!
    ];
    const ACTIONS = [
        'index' => [
            'icon' => 'tabler:list',
            'hover' => 'hover:text-blue-400',
            'default_text' => 'actions.index',
        ],
        'new' => [
            'icon' => 'tabler:plus',
            'hover' => 'hover:text-blue-400',
            'default_text' => 'actions.new',
        ],
        'show' => [
            'icon' => 'tabler:eye',
            'hover' => 'hover:text-blue-400',
            'default_text' => 'actions.show',
        ],
        'edit' => [
            'icon' => 'tabler:pencil',
            'hover' => 'hover:text-blue-400',
            'default_text' => 'actions.edit',
        ],
        'delete' => [
            'icon' => 'tabler:trash',
            'hover' => 'hover:text-red-600',
            'default_text' => 'actions.delete',
        ],
        '_undefined' => [
            'icon' => 'tabler:question-mark',
            'hover' => 'hover:text-blue-400',
            'default_text' => 'actions.index',
        ],
    ];
    const SIZES = [
        'xs' => [
            'button' => 'btn-xs',
            'icon' => 'inline-block size-3',
            'text' => '',
        ],
        'sm' => [
            'button' => 'btn-sm',
            'icon' => 'inline-block size-4',
            'text' => '',
        ],
        'md' => [
            'button' => '',
            'icon' => 'inline-block size-5',
            'text' => '',
        ],
        'lg' => [
            'button' => 'btn-lg',
            'icon' => 'inline-block size-6',
            'text' => '',
        ],
        'xl' => [
            'button' => 'btn-xl',
            'icon' => 'inline-block size-7',
            'text' => '',
        ],
    ];

    // public string $type;
    public string|false $action_path;
    // public string $action;
    public string|bool $icon = true;
    public ?string $icon_class;
    public ?string $btn_class;
    public ?string $text_class;
    public null|string|object $entity;
    public ?string $trans_domain;
    public bool|string $title;
    public null|string|bool $confirm;
    public array $size;

    public function __construct(
        protected AppWireServiceInterface $appWire,
    )
    {}

    public static function getActions(): array
    {
        $actions = array_keys(self::ACTIONS);
        return array_filter($actions, fn($action) => preg_match('/^(?!_)/', $action));
    }

    public function mount(
        string $type,
        bool $icon,
        string $action,
        null|string|object $entity = null,
        ?string $icon_class = null,
        ?string $btn_class = null,
        ?string $text_class = null,
        ?string $trans_domain = null,
        ?string $size = null,
        ?string $confirm = null,
        bool|string $title = true,
    ): void
    {
        $this->entity = $entity;
        $this->confirm = $confirm;
        $actions = static::getActions();
        if(in_array($action, $actions)) {
            // Entity action
            if(empty($entity)) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: action "%s" requires an entity to be set.', [__METHOD__, __LINE__, $action]));
            }
            $this->action_path = $this->getActionPath($entity, $action);
            if($action === 'delete' && $this->confirm !== false) {
                $this->confirm = true;
            }
            if($this->confirm === true) {
                // Auto confirm id
                $this->confirm = 'confirm-modal-'.$action.'-'.(is_object($entity) ? $entity->getId() : Encoders::getUniquid(Objects::getShortname($entity, true), '-'));
            }
        } else {
            // Custom action, should be a URL
            $this->action_path = filter_var($action, FILTER_VALIDATE_URL) ? $action : false;
            if($this->confirm === true) {
                // Auto confirm id
                $this->confirm = 'confirm-modal-'.Encoders::getUniquid(separator: '-');
            }
        }
        if($this->action_path) {
            $action_styles = self::ACTIONS[$action] ?? self::ACTIONS['_undefined'];
            // $this->action = $action;
            if(is_bool($icon) && $icon) {
                $this->icon = $action_styles['icon'];
            } else {
                // Icon name or false
                $this->icon = $icon;
            }
            $this->size = self::SIZES[$size] ?? self::SIZES['md'];
            $this->icon_class = Iterables::toClassList($this->size['icon'].' '.$action_styles['hover'].' '.$icon_class, true);
            $this->btn_class = Iterables::toClassList(static::TYPES[$type].' '.$this->size['button'].' '.$btn_class, true);
            $this->text_class = Iterables::toClassList($this->size['text'].' '.$text_class, true);
            $this->trans_domain = $trans_domain;
            $this->title = $title === true ? $action_styles['default_text'] : $title;
        }
    }

    #[PreMount()]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $actions = static::getActions();

        $resolver->setDefaults([
            'type' => 'button',
            'size' => null,
            // 'action' => null,
            // 'text' => null,
            'icon' => true,
            'entity' => null,
            'trans_domain' => 'default',
            'title' => true,
            // 'icon_class' => null,
            // 'btn_class' => null,
            // 'text_class' => null,
            'confirm' => null,
        ]);
        // type
        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedValues('type', ['button', 'link', 'ghost']);
        // size
        $resolver->setAllowedTypes('size', ['string', 'null']);
        $resolver->setDefault('size', 'md');
        $resolver->setAllowedValues('size', array_merge([null], array_keys(self::SIZES)));
        // action: action name or URL
        $resolver->setRequired('action');
        $resolver->setAllowedTypes('action', ['string']);
        // icon
        $resolver->setAllowedTypes('icon', ['string','bool']);
        // entity
        $etypes = in_array($data['action'], $actions) ? ['string', 'object'] : ['string','object', 'null'];
        $resolver->setAllowedTypes('entity', $etypes);
        // title
        $resolver->setAllowedTypes('title', ['string', 'bool']);
        // confirm
        $resolver->setAllowedTypes('confirm', ['string', 'bool', 'null']);
        // trans_domain
        $resolver->setAllowedTypes('trans_domain', ['string', 'null']);

        // trans_domain default by entity
        if(($data['trans_domain'] ?? 'default') === 'default') {
            $data['trans_domain'] = $data['entity'] instanceof ClassDescriptionInterface ? $data['entity']->getTrans_domain() : Objects::getShortname($data['entity']);
        }

        return $resolver->resolve($data) + $data;
    }

    // #[PostMount()]
    // public function postMount(array $data): array
    // {
    //     dump($this, $data);
    //     return $data;
    // }

    public function getActionPath(
        string|object $entity,
        string $action
    ): string|false
    {
        return $this->appWire->getActionPath($entity, $action);
    }

}