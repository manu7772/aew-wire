<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\WireWebsectionInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireWebsectionServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Files;
// Symfony
use Symfony\Component\Yaml\Yaml;
use Knp\Component\Pager\PaginatorInterface;
// PHP
use Exception;
use SplFileInfo;

abstract class WireWebsectionService implements WireWebsectionServiceInterface
{
    use TraitBaseService;
    use TraitBaseEntityService;

    public const ENTITY_CLASS = WireWebsectionInterface::class;

    public const FILES_FOLDER = 'websection/';
    public const CACHE_WS_MODELS_LIFE = null;
    public const SECTION_TYPES = ['section','header','footer','banner','sidemenu','left-sidemenu','right-sidemenu','hidden'];
    public const SEARCH_FILES_DEPTH = ['>=0','<2'];

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
    ) {
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        // Check all WireWebsectionInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    public function getPreferedWebsections(): array
    {
        /** @var ServiceEntityRepository */
        $repository = $this->getRepository();
        return $repository->findBy(['prefered' => true]);
    }

    public function getWebsectionsCount(
        bool $onlyActives = false,
        array $criteria = []
    ): int
    {
        if($onlyActives) {
            $criteria['enabled'] = true;
        }
        return $this->getCount($criteria);
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
                    $search = Files::getNewFinder()->files()->name('*.twig')->in($path)->depth(static::SEARCH_FILES_DEPTH);
                    foreach ($search as $file) {
                        $file2 = $file = new SplFileInfo($file->getPath().DIRECTORY_SEPARATOR.Files::stripTwigfile($file, true).'.yaml');
                        if(!$file->getRealpath()) $file = new SplFileInfo($file->getPath().DIRECTORY_SEPARATOR.Files::stripTwigfile($file, true).'.yml');
                        if($file->getRealpath()) {
                            $index = Files::stripTwigfile($file, true);
                            try {
                                $files[$index] = Yaml::parseFile($file->getRealPath());
                            } catch (\Throwable $th) {
                                throw new Exception(vsprintf('Error %s line %d: in file %s, an error occured%s- %s', [__METHOD__, __LINE__, $file->getRealPath(), PHP_EOL, $th->getMessage()]));
                            }
                            // $files[$index] = $this->appService->get('Tool:Files')->readYamlFile($yaml_file);
                            $files[$index]['choice_value'] = static::FILES_FOLDER.Files::stripTwigfile($file, false);
                            $files[$index]['choice_label'] ??= ucfirst(Files::stripTwigfile($file, true)).(isset($files[$index]['description']) ? '<i class="text-muted"> - '.$files[$index]['description'].'</i>' : '');
                        } else {
                            $file = $file2;
                            $content = file_get_contents($file->getRealpath());
                            preg_match('/(\{#\s*description\s*:\s*([\p{L}\s\?\,!-:;]+)\s*#\})/u', $content, $description);
                            preg_match('/(\{#\s*status\s*:\s*([\p{L}\s\?\,!-:;]+)\s*#\})/u', $content, $status);
                            preg_match('/(\{#\s*sectiontype\s*:\s*([\p{L}\s\?\,!-:;]+)\s*#\})/u', $content, $sectiontype);
                            preg_match('/(\{#\s*(default)\s*#\})/u', $content, $default);
                            if(!preg_match('/^\s*disabled/i', $status[2] ?? '')) {
                                $choice_value = $file->getRealpath();
                                if(!$choice_value) throw new Exception(vsprintf('Error %s line %d: path "%s" is invalid', [__METHOD__, __LINE__, $path]));
                                if(count($sectiontype) < 3) throw new Exception(vsprintf('Error %s line %d: section type (from %s) not found in section file "%s"', [__METHOD__, __LINE__, json_encode($sectiontype), $path]));
                                $choice_value = static::FILES_FOLDER.Files::stripTwigfile($file, false);
                                $files[Files::stripTwigfile($file, true)] = [
                                    'sectiontype' => trim($sectiontype[2]),
                                    'description' => trim($description[2]) ?? null,
                                    'status' => trim($status[2] ?? 'enabled'),
                                    'default' => !empty($default),
                                    'content' => $content,
                                    'choice_value' => $choice_value,
                                    'choice_label' => ucfirst(Files::stripTwigfile($file, true)).(count($description) > 2 ? '<i class="text-muted"> - '.$description[2].'</i>' : ''),
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
        $choicelist = [];
        foreach ($files as $model) {
            $choicelist[$model['choice_label']] = $model['choice_value'];
        }
        // dump($choicelist, $files);
        return $asChoiceList ? $choicelist : $files;
    }

    public function getSectiontypeOfFile(
        string $filename
    ): ?string
    {
        foreach ($this->listWebsectionModels(false) as $model) {
            // Try find by html.twig or yaml file
            if($filename === $model['choice_value'] || $filename === $model['file']) return $model['sectiontype'];
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