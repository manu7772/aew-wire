<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
// PHP
use SplFileInfo;

interface WireWebsectionServiceInterface extends WireEntityServiceInterface
{

    public function getPreferedWebsections(): array;
    public function getWebsectionsCount(
            bool $onlyActives = false,
            array $criteria = []
        ): int;
    public function findWebsection(int|string|null $websection): ?WireWebsectionInterface;
    // public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string;
    public function listWebsectionModels(
            bool $asChoiceList = false,
            array|string|null $filter_types = null,
            string|array|null $paths = null
        ): array;
    public function getSectiontypeOfFile(
            string $filename
        ): ?string;
    public function getSectiontypes(
            array|string|null $filter_types = null
        ): array;
    public function getWebsectionModels(
            array|string|null $filter_types = null
        ): array;
    public function getDefaultWebsectionModel(
            array|string|null $filter_types = null,
            bool $findAnyway = true,
        ): ?string;
    public function setDefaultWebsectionValues(
            WireWebsectionInterface $entity,
            mixed $data = [],
            $event = null,
        ): void;

}