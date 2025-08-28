<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent(
    name: 'wire:modal-confirm',
    template: '@AequationWire/components/modal-confirm.html.twig'
)]
class ModalConfirm
{

    public string $id;
    public bool|string $icon;
    public array $options = [];
    public ?string $title;

    public function mount(
        string $id,
        bool|string $icon = true,
        array $options = [],
        ?string $title = null
    ): void
    {
        $this->id = $id;
        $this->icon = is_bool($icon) ? ($icon ? 'tabler:question-mark' : false) : $icon;
        $this->options = $options;
        $this->title = $title;
    }

    #[PreMount()]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $resolver->setDefaults([
            'icon' => true,
            'options' => [],
            'title' => null,
        ]);

        $resolver->setRequired(['id']);
        $resolver->setAllowedTypes('id', ['string']);
        $resolver->setAllowedTypes('icon', ['string', 'bool']);
        $resolver->setAllowedTypes('options', ['array']);
        $resolver->setAllowedTypes('title', ['string', 'null']);

        return $resolver->resolve($data) + $data;
    }

    // #[PostMount()]
    // public function postMount(array $data): array
    // {
    //     dump($this, $data);
    //     return $data;
    // }

}