<?php

namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Service\NormalizerService;

class NormalizeOptionsContainer
{
    public const CONTEXT_NAME = 'normalize_options_container';

    private string $main_group = NormalizerService::MAIN_GROUP;
    private bool $create_only = true;
    private bool $is_model = false;
    private array $context = [];

    public function __construct(
        ?bool $create_only = null,
        ?bool $is_model = null,
        ?array $context = null,
        ?string $main_group = null,
    ) {
        if (is_array($context)) {
            $this->setContext($context);
        }
        if (is_bool($create_only)) $this->create_only = $create_only;
        if (is_bool($is_model)) {
            $is_model ? $this->setModel() : $this->setReal();
        }
        if (is_string($main_group) && !empty($main_group)) $this->main_group = $main_group;
    }


    private function integrateContainer(
        NormalizeOptionsContainer $container
    ): void {
        if ($container !== $this) {
            $this->create_only = $container->isCreate();
            $this->is_model = $container->isModel();
            $this->main_group = $container->getMainGroup();
            $context = $container->getContext();
            if (isset($context[static::CONTEXT_NAME])) {
                unset($context[static::CONTEXT_NAME]);
            }
            $this->setContext($context);
        }
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(
        array $context
    ): void {
        if (isset($context[static::CONTEXT_NAME]) && $context[static::CONTEXT_NAME] !== $this) {
            $this->integrateContainer($context[static::CONTEXT_NAME]);
        }
        $this->context = $context;
        $this->context[static::CONTEXT_NAME] = $this;
    }

    public function getNormalizationContext(
        ?array $context = null
    ): array {
        if (!is_array($context)) {
            $context = $this->context;
        }
        // Remove self container
        if (isset($context[static::CONTEXT_NAME])) unset($context[static::CONTEXT_NAME]);
        // Define groups if not
        if (empty($context['groups'])) {
            $context['groups'] = $this->getNormalizeGroups($context['type']);
        }
        return $context;
    }

    public function getDenormalizationContext(
        ?array $context = null
    ): array {
        if (!is_array($context)) {
            $context = $this->context;
        }
        // Remove self container
        if (isset($context[static::CONTEXT_NAME])) unset($context[static::CONTEXT_NAME]);
        // Define groups if not
        if (empty($context['groups'])) {
            $context['groups'] = $this->getDenormalizeGroups($context['type']);
        }
        return $context;
    }

    /**
     * Create only or find existing entity
     */

    public function setMainGroup(
        string $name
    ): static {
        $this->main_group = $name;
        return $this;
    }

    public function resetMainGroup(): static
    {
        return $this->setMainGroup(NormalizerService::MAIN_GROUP);
    }

    public function getMainGroup(): string
    {
        return $this->main_group;
    }

    public function getNormalizeGroups(
        WireEntityInterface|string $class
    ): array {
        return NormalizerService::getNormalizeGroups($class, $this->main_group);
    }

    public function getDenormalizeGroups(
        WireEntityInterface|string $class
    ): array {
        return NormalizerService::getDenormalizeGroups($class, $this->main_group);
    }

    /**
     * Create only or find existing entity
     */

    public function createOnly(): static
    {
        $this->create_only = true;
        return $this;
    }

    public function createOrFind(): static
    {
        // Can not find if is model
        $this->create_only = $this->is_model;
        return $this;
    }

    public function isCreate(): bool
    {
        return $this->create_only;
    }

    public function isFind(): bool
    {
        return !$this->create_only;
    }

    /**
     * Creating a model
     */

    public function setModel(): static
    {
        $this->is_model = true;
        $this->createOnly();
        $this->setMainGroup('model');
        return $this;
    }

    public function setReal(): static
    {
        $this->is_model = false;
        return $this;
    }

    public function isModel(): bool
    {
        return $this->is_model;
    }

    public function isReal(): bool
    {
        return !$this->is_model;
    }
}
