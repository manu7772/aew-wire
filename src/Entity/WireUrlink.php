<?php
namespace Aequation\WireBundle\Entity;

// Aequation
use Aequation\WireBundle\Attribute\ClassCustomService;
use Aequation\WireBundle\Attribute\Slugable;
use Aequation\WireBundle\Entity\interface\TraitRelinkableInterface;
use Aequation\WireBundle\Entity\interface\WireItemInterface;
use Aequation\WireBundle\Entity\interface\WireUrlinkInterface;
use Aequation\WireBundle\Entity\WireRelink;
use Aequation\WireBundle\Repository\WireUrlinkRepository;
use Aequation\WireBundle\Service\interface\WireUrlinkServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: WireUrlinkRepository::class)]
#[ClassCustomService(WireUrlinkServiceInterface::class)]
#[ORM\HasLifecycleCallbacks]
class WireUrlink extends WireRelink implements WireUrlinkInterface
{

    public const ICON = 'tabler:link';
    public const FA_ICON = 'link';

    public const RELINK_TYPE = 'URL';

    #[Gedmo\SortableGroup]
    protected WireItemInterface & TraitRelinkableInterface $itemowner;

    #[Gedmo\SortablePosition]
    protected int $position;


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