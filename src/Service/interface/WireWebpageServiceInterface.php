<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
// PHP
use SplFileInfo;

interface WireWebpageServiceInterface extends WireEntityServiceInterface
{

    public function getPreferedWebpage(): ?WireWebpageInterface;
    public function setMainMenuIfMissing(WireWebpageInterface $webpage): bool;
    public function getExposableWebpages(
        string|object $item,
        bool $onlyActiveWebpage = true
    ): array;
    public function getFirstExposableWebpage(
        string|object $item,
        bool $onlyActiveWebpage = true,
        bool $attributeToEntity = false
    ): ?WireWebpageInterface;
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
    public function getWebsectionsChoices(bool $filterActive = true): array;

}
