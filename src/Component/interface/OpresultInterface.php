<?php
namespace Aequation\WireBundle\Component\interface;

use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Markup;

interface OpresultInterface
{

    // Keep this order please
    public const ACTION_DANGER =        'danger';       // error -- operation failed
    public const ACTION_WARNING =       'warning';      // warning -- operation failed but not critical
    public const ACTION_UNDONE =        'undone';       // undone -- operation was unecessary
    public const ACTION_SUCCESS =       'success';      // success -- success
    public const MESSAGE_INFO =         'info';         // info message
    public const MESSAGE_DEV =          'dev';          // information for DEVELOPPERS

    public function isSuccess(): bool;
    public function hasSuccess(): bool;
    public function isUndone(): bool;
    public function hasUndone(): bool;
    public function isPartialSuccess(): bool;
    public function hasFail(): bool;
    public function isFail(): bool;
    public function isWarning(): bool;
    public function hasWarning(): bool;

    public function isContainerValid(): bool;
    public function getContainer(): array;
    public function getJsonContainer(): string;

    public function addResult(string $type, null|string|array $messages = null, int $inc = 1): static;
    public function addSuccess(null|string|array $messages = null, int $inc = 1): static;
    public function addUndone(null|string|array $messages = null, int $inc = 1): static;
    public function addWarning(null|string|array $messages = null, int $inc = 1): static;
    public function addDanger(null|string|array $messages = null, int $inc = 1): static;

    public function initActionTypes(bool $resetTypes = true): static;
    public function getActionTypes(): array;
    public function addActionType(string $type): static;
    public function resetActions(): static;
    public function checkActions(): static;
    public function getActions(null|string|array $types = null, bool $getTotal = false): array|int;

    public function getTotalActions(): int;
    public function addMessage(string $type, string|array $messages): static;
    public function resetMessages(): static;
    public function checkMessagesTypes(): static;
    public function getMessageTypes(): array;
    public function getMessages(?string $type = null): array;
    public function printMessages(SymfonyStyle|bool $asHtmlOrIo = false, string|array $msgtypes = null): void;
    public function getMessagesAsString(?bool $asHtml = null, bool $byTypes = true, string|array $msgtypes = null): string|Markup;
    public function hasMessages(?string $type = null): bool;
    public function getMessageGlobalType(): string;
    public function getData(null|string|int $index = null): mixed;
    public function addData(string|int $index, mixed $data): static;
    public function setData(mixed $data): static;

    public function dump(): array;

}