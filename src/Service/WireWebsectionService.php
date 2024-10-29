<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\interface\WireWebsectionServiceInterface;
// Symfony
use Symfony\Component\Yaml\Yaml;
// PHP
use Exception;

abstract class WireWebsectionService extends WireHtmlcodeService implements WireWebsectionServiceInterface
{

    public const ENTITY_CLASS = WireWebsectionInterface::class;
    public const FILES_FOLDER = 'websection/';
    public const CACHE_WS_MODELS_LIFE = null;
    public const SECTION_TYPES = ['section','header','footer','banner','sidemenu','left-sidemenu','right-sidemenu','hidden'];

    public function getPreferedWebsections(): array
    {
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->findBy(['prefered' => true, 'enabled' => true, 'softdeleted' => false]);
    }

    public function getWebsectionsCount(
        bool $onlyActives = false,
        array $criteria = []
    ): int
    {
        if($onlyActives) {
            $criteria['enabled'] = true;
            $criteria['softdeleted'] = false;
        }
        return $this->getEntitiesCount($criteria);
    }

    /**
     * Find Websection by Id or by Slug
     * If $websection is empty, returns prefered Websection in the database
     * @param integer|string|null $websection
     * @return WireWebsectionInterface|null
     */
    public function findWebsection(int|string|null $websection): ?WireWebsectionInterface
    {
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return preg_match('/^\d+$/', (string)$websection)
            ? $repository->find((int)$websection)
            : $repository->findOneBySlug($websection);
    }

    // public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string
    // {
    //     $basename = $twigfile instanceof SplFileInfo
    //         ? $twigfile->getFilename()
    //         : pathinfo($twigfile)['basename'];
    //     return $removeExtension
    //         ? preg_replace(static::TWIGFILE_MATCH, '', $basename)
    //         : $basename;
    // }

    public function listWebsectionModels(
        bool $asChoiceList = false,
        array|string|null $filter_types = null,
        string|array|null $paths = null
    ): array
    {
        if(empty($paths)) {
            $paths = [
                'templates/'.static::FILES_FOLDER,
            ];
        }
        $paths = (array)$paths;
        // $cache_name = 'list_ws_models_'.hash('md5',json_encode($paths));
        // $list = $this->appService->getCache()->get(
        //     key: $cache_name,
        //     callback: function(ItemInterface $item) use ($paths) {
        //         if(!empty(static::CACHE_WS_MODELS_LIFE)) {
        //             $item->expiresAfter(static::CACHE_WS_MODELS_LIFE);
        //         }
                $files = [];
                foreach ($paths as $path) {
                    $path = $this->appWire->getProjectDir($path);
                    $search = $this->getNewFinder()->files()->name('*.twig')->in($path)->depth(static::SEARCH_FILES_DEPTH);
                    foreach ($search as $file) {
                        // $file = new SplFileInfo($file->getPath().DIRECTORY_SEPARATOR.static::stripTwigfile($file, true).'.yaml');
                        // if(!$file->getRealpath()) $file = new SplFileInfo($file->getPath().DIRECTORY_SEPARATOR.static::stripTwigfile($file, true).'.yml');
                        if($file->getRealpath()) {
                            $index = static::stripTwigfile($file, true);
                            $files[$index] = Yaml::parseFile($file->getRealPath());
                            // $files[$index] = $this->appService->get('Tool:Files')->readYamlFile($yaml_file);
                            $files[$index]['choice_value'] = static::FILES_FOLDER.static::stripTwigfile($file, false);
                            $files[$index]['choice_label'] ??= ucfirst(static::stripTwigfile($file, true)).(isset($files[$index]['description']) ? '<i class="text-muted"> - '.$files[$index]['description'].'</i>' : '');
                        } else {
                            $content = $file->getContents();
                            preg_match('/(\{#\s*description\s*:\s*([\p{L}\s\?\,!-:;]+)\s*#\})/u', $content, $description);
                            preg_match('/(\{#\s*status\s*:\s*([\p{L}\s\?\,!-:;]+)\s*#\})/u', $content, $status);
                            preg_match('/(\{#\s*sectiontype\s*:\s*([\p{L}\s\?\,!-:;]+)\s*#\})/u', $content, $sectiontype);
                            preg_match('/(\{#\s*(default)\s*#\})/u', $content, $default);
                            if(!preg_match('/^\s*disabled/i', $status[2] ?? '')) {
                                $choice_value = $file->getRealpath();
                                if(!$choice_value) throw new Exception(vsprintf('Error %s line %d: path "%s" is invalid', [__METHOD__, __LINE__, $path]));
                                if(count($sectiontype) < 3) throw new Exception(vsprintf('Error %s line %d: section type (from %s) not found in section file "%s"', [__METHOD__, __LINE__, json_encode($sectiontype), $path]));
                                $choice_value = static::FILES_FOLDER.static::stripTwigfile($file, false);
                                $files[static::stripTwigfile($file, true)] = [
                                    'sectiontype' => trim($sectiontype[2]),
                                    'description' => trim($description[2]) ?? null,
                                    'status' => trim($status[2] ?? 'enabled'),
                                    'default' => !empty($default),
                                    'content' => $content,
                                    'choice_value' => $choice_value,
                                    'choice_label' => ucfirst(static::stripTwigfile($file, true)).(count($description) > 2 ? '<i class="text-muted"> - '.$description[2].'</i>' : ''),
                                ];
                            }
                        }
                    }
                }
        //         return $files;
        //     },
        //     commentaire: 'Websections models (paths: '.implode(' / ', $paths).')',
        // );
        if(!empty($filter_types)) {
            $filter_types = (array)$filter_types;
            $files = array_filter($files, function($model) use ($filter_types) { return in_array($model['sectiontype'], $filter_types); });
        }
        if(!$asChoiceList) return $files;
        $choicelist = [];
        foreach ($files as $model) {
            $choicelist[$model['choice_label']] = $model['choice_value'];
        }
        return $choicelist;
    }

    public function getSectiontypeOfFile(
        string $filename
    ): ?string
    {
        foreach ($this->listWebsectionModels() as $model) {
            if($filename === $model['choice_value']) return $model['sectiontype'];
        }
        return null;
    }

    public function getSectiontypes(
        array|string|null $filter_types = null
    ): array
    {
        $sectiontypes = [];
        foreach ($this->listWebsectionModels(asChoiceList: false, filter_types: $filter_types) as $section) {
            $sectiontypes[$section['sectiontype']] = $section['sectiontype'];
        }
        ksort($sectiontypes);
        return $sectiontypes;
    }

    public function getWebsectionModels(
        array|string|null $filter_types = null
    ): array
    {
        return $this->listWebsectionModels(asChoiceList: true, filter_types: $filter_types);
    }

    public function getDefaultWebsectionModel(
        array|string|null $filter_types = null,
        bool $findAnyway = true,
    ): ?string
    {
        $first = null;
        foreach ($this->listWebsectionModels(asChoiceList: false, filter_types: $filter_types) as $model) {
            $first ??= $model['choice_value'];
            if($model['default']) return $model['choice_value'];
        }
        return $findAnyway ? $first : null;
    }

    /**
     * Test event
     * @param WebsectionInterface $entity
     * @param mixed $data
     */
    // #[AppEvent(groups: [AppEvent::onCreate, Events::postLoad])]
    public function setDefaultWebsectionValues(
        WireWebsectionInterface $entity,
        mixed $data = [],
        $event = null,
    ): void
    {
        if(empty($entity->getTwigfile())) {
            $default = $this->getDefaultWebsectionModel($entity->getSectiontype());
            if(!empty($default)) $entity->setTwigfile($default);
        }
    }

}