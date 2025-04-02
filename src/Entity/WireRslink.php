<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WireRslinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

#[UniqueEntity(fields: ['name','parent'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WireRslink extends WireRelink implements WireRslinkInterface
{

    public const ICON = [
        'ux' => 'tabler:wifi',
        'fa' => 'fa-wifi'
    ];

    public const RELINK_TYPE = 'RS';

    // #[Gedmo\SortableGroup]
    // protected WireItemInterface & TraitRelinkableInterface $parent;

    // #[Gedmo\SortablePosition]
    // protected int $position;


    public function setUrl(?string $url): static
    {
        $this->mainlink = $url;
        return $this;
    }

    public function getUrl(
        ?int $referenceTypeIfRoute = Router::ABSOLUTE_PATH
    ): string
    {
        return $this->isUrl()
            ? $this->mainlink
            : $this->__estatus->appWire->getUrlIfExists($this->mainlink, $this->params, $referenceTypeIfRoute);
    }

    public function setRoute(?string $route): static
    {
        $this->mainlink = $route;
        return $this;
    }

    public function getRoute(): string
    {
        return $this->mainlink;
    }

}