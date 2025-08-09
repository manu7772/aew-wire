<?php
namespace Aequation\WireBundle\Twig\Components;

// Symfony

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(
    name: 'wire:print-value',
    template: '@AequationWire/components/print-value.html.twig'
)]
class PrintValue extends AbstractController
{
    #[ExposeInTemplate(name: 'value')]
    public mixed $value = null;
    #[ExposeInTemplate(name: 'property')]
    public ?string $property = null;
    #[ExposeInTemplate(name: 'levels')]
    public int $levels = 3;
    #[ExposeInTemplate(name: 'from')]
    public null|object|array $from = null;
    #[ExposeInTemplate(name: 'classname')]
    public ?string $classname = '';
    #[ExposeInTemplate(name: 'debug')]
    public bool $debug = false;

    public PropertyAccessorInterface $accessor;

    public function __construct(
    ) {
        $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
    }


    public function getValue(): mixed
    {
        if ($this->from && $this->property && is_null($this->value)) {
            try {
                $this->value = is_object($this->from) ? $this->accessor->getValue($this->from, $this->property) : $this->from[$this->property] ?? null;
            } catch (\Throwable $th) {
                $this->value = null;
                // dump($this->from, $this->property);
                // throw new Exception(vsprintf('Error %s line %d: property "%s" not readable. %s', [__METHOD__, __LINE__, $this->property, $th->getMessage()]));
            }
        }
        return $this->value;
    }

}