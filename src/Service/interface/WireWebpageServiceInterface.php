<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
// PHP
use SplFileInfo;

interface WireWebpageServiceInterface extends WireEntityServiceInterface
{

    public function getPreferedWebpage(): ?WireWebpageInterface;
    public function getWebpageFor(string|BaseEntityInterface $entity, bool $attributeToEntity = false, bool $onlyActiveWebpage = true): ?WireWebpageInterface;
    public function getWebpagesCount(
            bool $onlyActives = false,
            array $criteria = []
        ): int;
    public function findWebpage(int|string|null $webpage): ?WireWebpageInterface;
    // public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string;
    public function listWebpageModels(
            string|array|null $paths = null,
            bool $asChoiceList = false
        ): array;
    public function getWebpageModels(): array;
    public function getDefaultWebpageModel(): ?string;

}
