<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WireRslinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name','ownereuid'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WireRslink extends WireRelink implements WireRslinkInterface
{

    public const ICON = [
        'ux' => 'tabler:wifi',
        'fa' => 'fa-wifi'
    ];

    public const RELINK_TYPE = 'RS';


    #[Assert\NotNull(message: 'Le lien URL du réseau social est obligatoire', groups: ['persist','update'])]
    protected ?string $mainlink = null;

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
            : $this->getEmbededStatus()->appWire->getUrlIfExists($this->mainlink, $this->params, $referenceTypeIfRoute);
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