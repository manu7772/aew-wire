<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Component\interface\MenuItemInterface;
use Aequation\WireBundle\Component\interface\TypedCollectionInterface;
use Aequation\WireBundle\Component\MenuItem;
use Aequation\WireBundle\Entity\interface\WireEcollectionInterface;
use Aequation\WireBundle\Entity\interface\WireMenuInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Twig\interface\DynamicTemplateInterface;
use Doctrine\Common\Collections\ArrayCollection;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
// PHP
use InvalidArgumentException;

#[AsTwigComponent(
    name: 'wire:menu',
    // template: '@AequationWire/components/wire/menu.html.twig'
)]
class MenuComponent extends AbstractController implements DynamicTemplateInterface
{

    public const MAX_LEVELS = 3;

    public const BASE_TEMPLATE_PATH = '@AequationWire/components/wire/';
    public const AVAILABLE_TEMPLATES = [
        '@default' => 'menu.html.twig',
        '@public_menu' => 'menus/menu_001.html.twig',
        '@admin_sidebar' => 'menus/menu_002.html.twig',
    ];

    protected readonly WireEntityManagerInterface $wireEm;
    // protected readonly ?Request $request;
    protected readonly null|WireMenuInterface|ArrayCollection $menu;
    protected int $max_levels;
    protected ?bool $fwFilter;
    // public bool $activeFilter = true; // filter active elements
    protected readonly ?string $template;
    protected MenuItemInterface $compiled;

    /**
     * Initializes a new MenuComponent.
     */

    public function __construct(
        protected readonly AppWireServiceInterface $appWire,
    )
    {
        $this->wireEm = $this->appWire->get(WireEntityManagerInterface::class);
        // $this->request = $this->appWire->getRequest();
        $this->max_levels = self::MAX_LEVELS;
    }


    public function mount(
        int|string|WireMenuInterface|ArrayCollection $menu,
        ?bool $fwFilter = null, // null = [auto] filter based on firewall, true = filter (remove inactive elements), false = no filter
        // bool $activeFilter = true,
        ?string $template = null,
    )
    {
        if($menu instanceof WireMenuInterface) {
            $this->menu = $menu;
        } else if($menu instanceof ArrayCollection) {
            $this->menu = $menu;
        } else {
            if(is_int($menu) || ctype_digit((string) $menu)) {
                // ID provided
                $this->menu = $this->wireEm->getRepository(WireMenuInterface::class)->find($menu);
            } else {
                $this->menu = $this->wireEm->getRepository(WireMenuInterface::class)->findOneBy(['slug' => $menu]);
            }
            if(!($this->menu instanceof WireMenuInterface)) {
                throw new InvalidArgumentException(vsprintf('Error %s line %d: The entity identified by "%s" is not a %s.', [__METHOD__, __LINE__, $menu, WireMenuInterface::class]));
            }
        }
        // $this->fwFilter = is_bool($fwFilter) ? $fwFilter : $this->appWire->isPublic();
        $this->fwFilter = $fwFilter;
        // $this->activeFilter = $activeFilter;
        // $this->template = $this->getTemplate($template);
        $this->template = $template;
        // dump($this);
    }


    public function getTemplate(): string
    {
        $default = array_keys(static::AVAILABLE_TEMPLATES)[0];
        $name = $this->template ?? $default;
        return preg_match('/^@/', $name) ? static::BASE_TEMPLATE_PATH . (static::AVAILABLE_TEMPLATES[$name] ?? static::AVAILABLE_TEMPLATES[$default]) : $name;
    }

    public function getElements(): ?MenuItemInterface
    {
        if(!isset($this->compiled)) {
            $options = [
                'fwFilter' => $this->fwFilter,
            ];
            $this->compiled = new MenuItem($this->menu, $options, $this->appWire, null);
        }
        return $this->compiled->isValid() ? $this->compiled : null;
    }



}