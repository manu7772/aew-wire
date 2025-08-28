<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent(
    name: 'wire:csstheme-switcher',
    template: '@AequationWire/components/csstheme-switcher.html.twig'
)]
class CssthemeSwitcher
{
    public string $firewall = 'current';
    public string $icon = 'tabler:blend-mode';

    public function mount(
        string $firewall
    ): void {
        $this->firewall = $firewall;
    }

    #[PreMount()]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);

        $resolver->setDefaults([
            'firewall' => 'current',
        ]);

        $resolver->setAllowedTypes('firewall', ['string']);

        return $resolver->resolve($data) + $data;
    }

    // #[PostMount()]
    // public function postMount(array $data): array
    // {
    //     dump($this, $data);
    //     return $data;
    // }

}