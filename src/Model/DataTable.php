<?php
namespace Aequation\WireBundle\Model;

class DataTable
{

    protected array $options = [];
    protected array $attributes = [];

    public function __construct(
        public readonly ?string $id = null
    ) {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(
        array $options
    ): static
    {
        $this->options = $options;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(
        array $attributes
    ): static
    {
        $this->attributes = $attributes;
        return $this;
    }

}