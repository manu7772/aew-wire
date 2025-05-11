<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Doctrine\ORM\Events;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name','ownereuid'], groups: ['persist','update'], message: 'Le nom {{ value }} est déjà utilisé.')]
#[ORM\HasLifecycleCallbacks]
abstract class WireUrlink extends WireRelink implements WireUrlinkInterface
{

    public const ICON = [
        'ux' => 'tabler:link',
        'fa' => 'fa-link'
    ];
    // public const DO_EMBED_STATUS_EVENTS = [Events::postLoad];
    public const RELINK_TYPE = 'URL';


    #[Assert\NotNull(message: 'Le lien URL est obligatoire', groups: ['persist','update'])]
    protected ?string $mainlink = null;

    public function getALink(
        ?int $referenceType = null
    ): ?string
    {
        if($this->isUrl()) {
            return $this->mainlink;
        } else if($this->isRoute()) {
            return $this->getEmbededStatus()->appWire->getUrlIfExists($this->mainlink, $this->params, $referenceType ?? Router::ABSOLUTE_PATH);
        }
        return null;
    }

    public function setUrl(?string $url): static
    {
        $this->mainlink = $url;
        return $this;
    }

    public function getUrl(
        ?int $referenceTypeIfRoute = Router::ABSOLUTE_PATH
    ): ?string
    {
        return empty($this->mainlink) || $this->isUrl()
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