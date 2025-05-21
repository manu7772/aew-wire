<?php
namespace Aequation\WireBundle\Component\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
// PHP
use ArrayAccess;
use Stringable;
use Twig\Markup;

interface EntityContainerInterface extends Stringable, ArrayAccess
{
    public const STD_ASSOCIATIONS_MAX_LEVEL = 5;
    public const MODELS_ASSOCIATIONS_MAX_LEVEL = 1;
    // public const UNSERIALIZABLE_FIELDS = ['uname'];
    public const EXTRA_DATA_NAME = '_extra_data';
    // contexts
    public const CONTEXT_MAIN_GROUP = 'context_main_group';
    public const CONTEXT_AS_MODEL = 'context_as_model';
    public const CONTEXT_DO_NOT_UPDATE = 'context_do_not_update';
    public const CONTEXT_DO_NOT_CREATE = 'context_do_not_create';
    // controls
    public const TRIGGER_EXCEPTION_ON_ERROR = false;
    public const THROW_EXCEPTION_ON_ERROR_NOW = true;

    public function __construct(
        NormalizerServiceInterface|EntityContainerInterface $starter,
        string $classname,
        array $data,
        array $context = [],
        ?string $parentProperty = null,
    );

    public function isValid(): bool;
    public function getControls(): OpresultInterface;
    public function getErrorMessages(): array;
    public function getMessagesAsString(?bool $asHtml = null, bool $byTypes = true, null|string|array $msgtypes = null): string|Markup;
    public function getLevel(): int;
    public function isMaxLevel(): bool;
    public function isRoot(): bool;
    public function getParent(): ?EntityContainerInterface;
    public function getClassname(): string;
    public function getShortname(): string;
    public function getCompiledData(): array;
    public function getRawdata(): array;
    public function isLoaded(): bool;
    public function isCreated(): bool;
    public function getRelationMapper(): RelationMapperInterface;
    public function getInfo(string|array $groups = [], ?string $message = null): array;
    // Entity
    public function setEntity(BaseEntityInterface $entity): void;
    public function getEntity(): ?BaseEntityInterface;
    public function getEntityDenormalized(?string $format = null, array $context = []): ?BaseEntityInterface;
    public function hasEntity(): bool;
    // Context
    public function getContext(): array;
    public function setContext(array $context): static;
    public function addContext(string $key, mixed $value): static;
    public function removeContext(string $key): static;
    public function mergeContext(array $context, bool $replace = true): static;
    public function getDenormalizationContext(): array;
    // Groups
    public function setMainGroup(?string $main_group): static;
    public function resetMainGroup(): static;
    public function getMainGroup(): string;
    // Options
    public function isCreateOnly(): bool;
    public function isUpdateOnly(): bool;
    public function isCreateOrUpdateAllowed(): bool;
    public function isModel(): ?bool;
    public function isEntity(): ?bool;

}