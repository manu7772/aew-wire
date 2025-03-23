<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;

class TwigfileMetadata
{

    public readonly ?array $models;
    public readonly ?array $sectiontypes;
    public ?string $defaultSectiontype;

    public function __construct(
        public readonly WireWebsectionInterface $websection,
        public readonly string|array|null $paths = null
    )
    {
        $this->sectiontypes = $this->websection->_service->getSectiontypes();
        $this->models = $this->websection->_service->listWebsectionModels(false, null, $paths);
    }

    public function getModelData(
        ?string $twigfile = null
    ): ?array
    {
        $twigfile ??= $this->websection->getTwigfile();
        foreach ($this->models as $data) {
            if($data['choice_value'] === $twigfile) return $data;
        }
        return null;
    }

    public function listWebsectionModels(
        bool $asChoiceList = false,
        array|string|null $filter_types = null,
        string|array|null $paths = null
    ): array
    {
        return $this->websection->_service->listWebsectionModels($asChoiceList, $filter_types, $paths);
    }

    public function getSectiontypeChoices(): array
    {
        return $this->sectiontypes;
    }

    public function getDefaultSectiontype(): string
    {
        if(!isset($this->defaultSectiontype)) {
            $sectiontypes = $this->getSectiontypeChoices();
            $this->defaultSectiontype = isset($sectiontypes['section'])
                ? $sectiontypes['section']
                : reset($sectiontypes);
        }
        return $this->defaultSectiontype;
    }

}