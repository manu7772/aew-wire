<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Entity\interface\WireWebpageInterface;
use Aequation\WireBundle\Service\interface\WireWebpageServiceInterface;
use Aequation\WireBundle\Service\interface\WireWebsectionServiceInterface;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\EntityRepository;
// PHP
use Exception;

abstract class WireWebpageService extends WireItemService implements WireWebpageServiceInterface
{

    public const ENTITY_CLASS = WireWebpageInterface::class;

    public const CACHE_WP_MODELS_LIFE = null;
    public const FILES_FOLDER = 'webpage/';
    public const SEARCH_FILES_DEPTH = ['>=0','<2'];

    protected array $defaultWebpages = [];

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult = parent::checkDatabase($opresult, $repair);
        // Check all WireWebpageInterface entities
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    /**
     * Create a new WireMenu entity.
     * 1. Add default/prefered Websections.
     * 
     * @param array|false $data
     * @param array $context
     * @return WireWebpageInterface
     */
    public function createEntity(
        array|false $data = false, // ---> do not forget uname if wanted!
        array $context = []
    ): WireWebpageInterface
    {
        /** @var WireWebpageInterface */
        $entity = $this->wireEm->createEntity($this->getEntityClassname(), $data, $context, false); // false = do not try service IMPORTANT!!!
        // 1. Add default/prefered Websections
        foreach ($this->appWire->get(WireWebsectionServiceInterface::class)->getPreferedWebsections() as $websection) {
            $entity->addWebsection($websection);
        }
        return $entity;
    }

    public function getPreferedWebpage(): ?WireWebpageInterface
    {
        /** @var EntityRepository */
        $repository = $this->getRepository();
        return $repository->findOneBy(['prefered' => true, 'enabled' => true]);
    }

    public function getWebpageFor(
        string|BaseEntityInterface $entity,
        bool $attributeToEntity = false,
        bool $onlyActiveWebpage = true
    ): ?WireWebpageInterface
    {
        if(is_a($entity, TraitWebpageableInterface::class, true)) {
            if($entity instanceof TraitWebpageableInterface && $entity->hasWebpage()) {
                $webpage = $entity->getWebpage();
                if($webpage->isActive() || !$onlyActiveWebpage) {
                    return $webpage;
                }
            }
            $classname = is_object($entity) ? $entity::class : $entity;
            if(!$this->wireEm->entityExists($classname, true, true)) {
                throw new Exception(vsprintf('Error %s line %d: entity "%s" does not exist in the database!', [__METHOD__, __LINE__, $classname]));
            }
            if(!isset($this->defaultWebpages[$classname])) {
                $this->defaultWebpages[$classname] = null;
                if(($uname = $entity::getDefaultWebpageUname()) && ($webpage = $this->wireEm->findEntityByUname($uname))) {
                    /** @var WireWebpageInterface $webpage */
                    $this->defaultWebpages[$classname] = $webpage->isActive() || !$onlyActiveWebpage ? $webpage : null;
                    if($this->defaultWebpages[$classname] && $attributeToEntity && $entity instanceof TraitWebpageableInterface) {
                        $entity->setWebpage($this->defaultWebpages[$classname]);
                    }
                }
            }
            return $this->defaultWebpages[$classname];
        }
        return null;
    }

    public function getWebpagesCount(
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
     * Find Webpage by Id or by Slug
     * If $webpage is empty, returns prefered Webpage in the database
     * @param integer|string|null $webpage
     * @return WireWebpageInterface|null
     */
    public function findWebpage(int|string|null $webpage): ?WireWebpageInterface
    {
        if(empty($webpage)) return $this->getPreferedWebpage();
        /** @var EntityRepository */
        $repository = $this->getRepository();
        return preg_match('/^\\d+$/', (string)$webpage)
            ? $repository->find((int)$webpage)
            : $repository->findOneBy(['slug' => $webpage]);
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

    public function listWebpageModels(
        string|array|null $paths = null,
        bool $asChoiceList = false
    ): array
    {
        if(empty($paths)) {
            $paths = [
                'templates/'.static::FILES_FOLDER,
            ];
        }
        $paths = (array)$paths;
        // $cache_name = 'list_pw_models_'.hash('md5',json_encode($paths));
        // $list = $this->appService->getCache()->get(
        //     key: $cache_name,
        //     callback: function(ItemInterface $item) use ($paths) {
        //         if(!empty(static::CACHE_WP_MODELS_LIFE)) {
        //             $item->expiresAfter(static::CACHE_WP_MODELS_LIFE);
        //         }
                $files = [
                    'choicelist' => [],
                    'info' => [],
                ];
                foreach ($paths as $path) {
                    $path = $this->appWire->getProjectDir($path);
                    $search = Files::getNewFinder()->files()->name('*.twig')->in($path)->depth(static::SEARCH_FILES_DEPTH);
                    foreach ($search as $file) {
                        $content = $file->getContents();
                        preg_match('/(\\{#\\s*description\\s*:\\s*([\\p{L}\\s\\?\\,!-:;]+)\\s*#\\})/u', $content, $description);
                        preg_match('/(\\{#\\s*status\\s*:\\s*([\\p{L}\\s\\?\\,!-:;]+)\\s*#\\})/u', $content, $status);
                        preg_match('/(\\{#\\s*(default)\\s*#\\})/u', $content, $default);
                        if(!preg_match('/^\\s*disabled/i', $status[2] ?? '')) {
                            $choice_label = $file->getRealpath();
                            if(!$choice_label) throw new Exception(vsprintf('Error %s line %d: path "%s" is invalid', [__METHOD__, __LINE__, $path]));
                            $choice_label = static::FILES_FOLDER.Files::stripTwigfile($file, false);
                            $files['choicelist'][ucfirst(Files::stripTwigfile($file, true)).(count($description) > 2 ? '<i class="text-muted"> - '.$description[2].'</i>' : '')] = $choice_label;
                            $files['info'][Files::stripTwigfile($file, true)] = [
                                'description' => $description[2] ?? null,
                                'status' => $status[2] ?? 'enabled',
                                'default' => !empty($default),
                                'content' => $content,
                                'choice_label' => $choice_label,
                            ];
                        }
                    }
                }
        //         return $files;
        //     },
        //     commentaire: 'Webpages models (paths: '.implode(' / ', $paths).')',
        // );
        return $asChoiceList
            ? $files['choicelist']
            : $files['info'];
    }

    public function getWebpageModels(): array
    {
        return $this->listWebpageModels(asChoiceList: true);
    }

    public function getDefaultWebpageModel(): ?string
    {
        foreach ($this->listWebpageModels(asChoiceList: false) as $model) {
            if($model['default']) return $model['choice_label'];
        }
        return null;
    }

    // /**
    //  * Test event
    //  * @param Webpage $entity
    //  * @param mixed $data
    //  */
    // #[AppEvent(groups: [AppEvent::onCreate, AppEvent::onLoad])]
    // public function setDefaultWebpageValues(
    //     Webpage $entity,
    //     mixed $data = [],
    //     $event = null,
    // ): void
    // {
    //     if(empty($entity->getTwigfile())) {
    //         $default = $this->getDefaultWebpageModel();
    //         if(!empty($default)) $entity->setTwigfile($default);
    //     }
    //     if($entity->_appManaged->isNew() && empty($entity->getMainmenu())) {
    //         /** @var EntityRepository */
    //         $repository = $this->getRepository(Menu::class);
    //         $default = $repository->findOneBy(['prefered' => true]);
    //         if(!empty($default)) $entity->setMainmenu($default);
    //     }
    // }

}