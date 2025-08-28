<?php
namespace Aequation\WireBundle\Twig\Components;

use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use BadMethodCallException;
use InvalidArgumentException;
use Knp\Component\Pager\Pagination\PaginationInterface;
// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(
    name: 'wire:entity-index',
    template: '@AequationWire/components/entity-index.html.twig'
)]
class EntityIndex
{

    // use DefaultActionTrait;

    public array $options;

    #[ExposeInTemplate(name: 'title')]
    public ?string $title = null;
    #[ExposeInTemplate(name: 'classname')]
    public string $classname;
    #[ExposeInTemplate(name: 'contextData')]
    public readonly PaginatedContextDataInterface $contextData; 

    public readonly WireEntityServiceInterface $service;
    // public WireClassMetadataInterface $wCmd;

    public function __construct(
        public WireEntityManagerInterface $wireEm
    ) {
        // $this->wCmd = $this->service->getWireClassMetadata();
        // dump($this);
    }

    public function mount(
        string $classname,
        array $options = [],
    ): void
    {
        $this->classname = $classname;
        $this->options = $options;
        $this->service = $this->wireEm->getEntityService($this->classname);
        $this->contextData ??= $this->service->getPaginatedContextData($this->options);
        // dump($this);
    }

    public function getService(): WireEntityServiceInterface
    {
        return $this->service;
    }

    public function getContextData(): PaginatedContextDataInterface
    {
        return $this->contextData;
    }

    public function __get($name)
    {
        if (property_exists($this->getContextData(), $name)) {
            return $this->getContextData()->$name;
        }
        throw new InvalidArgumentException(sprintf('Property or method "%s" does not exist in %s.', $name, static::class));
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments) && property_exists($this->getContextData(), $name)) {
            return $this->getContextData()->$name;
        }
        if (method_exists($this->getContextData(), $name)) {
            return $this->getContextData()->$name(...$arguments);
        }
        throw new BadMethodCallException(sprintf('Method "%s" does not exist in %s.', $name, static::class));
    }

    public function __isset($name): bool
    {
        return property_exists($this->getContextData(), $name);
    }

}