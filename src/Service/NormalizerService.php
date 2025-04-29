<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\EntityContainer;
use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\RelationMapperInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Component\RelationMapper;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Psr\Log\LoggerInterface;
// PHP
use ArrayObject;
use Exception;
use SplFileInfo;

/**
 * Normalizer service
 * @see https://symfony.com/doc/current/serializer.html
 */
#[AsAlias(NormalizerServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class NormalizerService implements NormalizerServiceInterface
{
    use TraitBaseService;
    
    public const CIRCULAR_REFERENCE_LIMIT = 2;

    protected SplFileInfo $currentPath;
    public array $yamlDatas = [];
    protected ArrayCollection $createds;
    public readonly array $entityNames;
    public array $relationMappers = [];
    public array $filecontents = [];
    public array $catalogue;

    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wireEm,
        public readonly SerializerInterface $serializer,
        public readonly ValidatorInterface $validator,
        public readonly LoggerInterface $logger
    ) {
        // Set the default path for the generator
        $this->setCurrentPath(null);
        $this->createds = new ArrayCollection();
    }

    public function getSerializer(): SerializerInterface & NormalizerInterface & DenormalizerInterface
    {
        return $this->serializer;
    }


    /****************************************************************************************************/
    /** DOCUMENTATION                                                                                   */
    /****************************************************************************************************/
    /**
     * How to Use the Serializer
     * @see https://symfony.com/doc/current/serializer.html
     * 
     * Serialization/Normalization
     * - Normalization depth
     * @see https://symfony.com/doc/current/serializer.html#handling-serialization-depth
     * - Callback to serialize properties with object instances
     * When serializing, you can set a callback to format a specific object property. This can be used instead of defining the context for a group:
     * @see https://symfony.com/doc/current/serializer.html#using-callbacks-to-serialize-properties-with-object-instances
     * 
     * Deserialization/Denormalization
     * - Objects with parameters in constructor
     * @see https://symfony.com/doc/current/serializer.html#advanced-deserialization
     * 
     * 
     */

    /****************************************************************************************************/
    /** CREATED                                                                                         */
    /****************************************************************************************************/

    public function addCreated(BaseEntityInterface $entity): void
    {
        $index = spl_object_hash($entity);
        if (!$this->hasCreated($entity)) {
            $this->createds->set($index, $entity);
            // if($entity instanceof WireMenuInterface) dump($index.' => '.$entity->getName().' => U:'.$entity->getUnameName().' / Model: '.($entity->getSelfState()->isModel() ? 'true' : 'false'));
        } else {
            $exists = $this->createds->get($index);
            if ($this->appWire->isDev()) {
                throw new Exception(vsprintf('Error %s line %d: entity with %s already saved in "createds" data!%s- 1 - %s %s%s- 2 - %s %s', [__METHOD__, __LINE__, $index, PHP_EOL, $entity->getClassname(), $entity, PHP_EOL, $exists->getClassname(), $exists]));
            }
            $this->logger->warning(vsprintf('Warning %s line %d: entity %s already saved in "createds" data as %s!', [__METHOD__, __LINE__, $entity, $exists]));
        }
    }

    public function hasCreated(BaseEntityInterface $entity): bool
    {
        return $this->createds->contains($entity);
        // return $this->createds->containsKey(spl_object_hash($entity));
    }

    public function clearCreateds(): bool
    {
        // foreach ($this->createds as $key => $entity) {
        //     /** @var BaseEntityInterface $entity */
        //     $this->createds->removeElement($entity);
        //     // $entity->getSelfState()->setDetached();
        //     // unset($entity);
        // }
        $this->createds->clear();
        return $this->createds->isEmpty();
    }

    /**
     * remove entity from persisted entities
     * Returns true if createds list is empty
     * 
     * @param BaseEntityInterface $entity
     * @return bool
     */
    public function clearPersisteds(): bool
    {
        $this->createds = $this->createds->filter(fn($entity) => !$entity->__estatus->isContained());
        return $this->createds->isEmpty();
    }

    public function findCreated(string $euidOrUname): ?BaseEntityInterface
    {
        foreach ($this->createds as $entity) {
            /** @var BaseEntityInterface $entity */
            if (
                ($entity instanceof BaseEntityInterface && $entity->getEuid() === $euidOrUname)
                || ($entity instanceof TraitUnamedInterface && $entity->getUnameName() === $euidOrUname)
            ) {
                dump('- FindCreated: => '.Objects::toDebugString($entity));
                return $entity;
            }
        }
        return null;
    }


    /****************************************************************************************************/
    /** CLEANING / PREPARING                                                                            */
    /****************************************************************************************************/

    /**
     * Get (de)normalization groups for a class
     * returns array of 2 named elements:
     *      - ["normalize" => <normalization groups>]
     *      - ["denormalize" => <denormalization groups>]
     * @param string|BaseEntityInterface $class
     * @param string $type
     * @return array
     */
    private static function _getGroups(
        string|BaseEntityInterface $class,
        string $type
    ): array {
        if ($class instanceof BaseEntityInterface) {
            $class = $class->getClassname();
        }
        $shortname = null;
        if (class_exists($class) || interface_exists($class)) {
            $shortname = Objects::getShortname($class, true);
        } else {
            // return [];
            // throw new Exception(vsprintf('Error %s line %d: Class %s not found or not instance of %s!', [__METHOD__, __LINE__, $class, BaseEntityInterface::class]));
        }
        $types = static::NORMALIZATION_GROUPS[$type] ?? static::NORMALIZATION_GROUPS['_default'];
        if (empty($type) || $type === '_default') $type = static::MAIN_GROUP;
        $groups = [
            'normalize' => [],
            'denormalize' => [],
        ];
        if($shortname) {
            foreach ($types as $name => $values) {
                foreach ($values as $group_name) {
                    $groups[$name][] = preg_replace(['/__shortname__/', '/__type__/'], [$shortname, $type], $group_name);
                }
            }
            // Control
            foreach ($groups as $n => $grps) {
                if(empty($grps)) {
                    throw new Exception(vsprintf('Error %s line %d: in %s context, no groups found for %s with type "%s"!', [__METHOD__, __LINE__, $n, $class, $type]));
                }
                foreach ($grps as $grp) {
                    if(!is_string($grp)) {
                        throw new Exception(vsprintf('Error %s line %d: in %s context, one of groups for %s with type "%s" is not a string, got %s!', [__METHOD__, __LINE__, $n, $class, $type, gettype($grp)]));
                    }
                    if(empty($grp) || preg_match('/\\.$/', $grp)) {
                        throw new Exception(vsprintf('Error %s line %d: in %s context, one of groups for %s with type "%s" is empty or not valid. Got "%s"!', [__METHOD__, __LINE__, $n, $class, $type, $grp]));
                    }
                }
            }
        }
        return $groups;
    }

    /**
     * Get normalization groups for a class
     * @param string|BaseEntityInterface $class
     * @param string $type
     * @return array
     */
    public static function getNormalizeGroups(
        string|BaseEntityInterface $class,
        ?string $type = null, // ['hydrate','model','clone','debug'...]
    ): array {
        return static::_getGroups($class, $type)['normalize'];
    }

    /**
     * Get denormalization groups for a class
     * @param string|BaseEntityInterface $class
     * @param string $type
     * @return array
     */
    public static function getDenormalizeGroups(
        string|BaseEntityInterface $class,
        ?string $type = null, // ['hydrate','model','clone','debug'...]
    ): array {
        return static::_getGroups($class, $type)['denormalize'];
    }

    // public function getMaxDepthHandler(): callable
    // {
    //     return function (mixed $innerObject, object $outerObject, string $attributeName, ?string $format = null, array $context = []): ?string {
    //         // return only the EUID of the next entity in the tree
    //         return $innerObject instanceof BaseEntityInterface ? $innerObject->getEuid() : null;
    //     };
    // }

    public function getCallbackHandler(): array
    {
        return [
            // all callback parameters are optional (you can omit the ones you don't use)
            // 'createdAt' => function (?object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            //     return $attributeValue instanceof DateTimeInterface ? $attributeValue->format(DateTimeInterface::ATOM) : '';
            // },
            // 'updatedAt' => function (?object $attributeValue, object $object, string $attributeName, ?string $format = null, array $context = []) {
            //     return $attributeValue instanceof DateTimeInterface ? $attributeValue->format(DateTimeInterface::ATOM) : '';
            // },
        ];
    }

    /****************************************************************************************************/
    /** NORMALIZER                                                                                      */
    /****************************************************************************************************/

    public function normalize(
        mixed $data,
        ?string $format = null,
        ?array $context = [],
        ?bool $convertToArrayList = false // for React, can not be object, but array
    ): array|string|int|float|bool|ArrayObject|null {
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ??= false;
        $context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_LIMIT] ??= static::CIRCULAR_REFERENCE_LIMIT;
        $context[AbstractNormalizer::CALLBACKS] ??= $this->getCallbackHandler();
        // $context[AbstractObjectNormalizer::MAX_DEPTH_HANDLER] ??= $this->getMaxDepthHandler();
        if ($data instanceof Collection) $data = $data->toArray();
        if ($convertToArrayList && is_array($data) && !array_is_list($data)) $data = array_values($data);
        $norm = $this->getSerializer()->normalize($data, $format, $context);
        ksort($norm);
        return $norm;
    }

    public function denormalize(
        mixed $data,
        string $classname,
        ?string $format = null,
        ?array $context = []
    ): mixed {
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        if (is_a($classname, BaseEntityInterface::class, true)) {
            // throw new Exception(vsprintf('Error %s line %d: please, use method denormalizeEntity() to denormalize entities!', [__METHOD__, __LINE__]));
            return $this->denormalizeEntity($data, $classname, $format, $context);
        }
        $this->wireEm->incDebugMode();
        $data = $this->getSerializer()->denormalize($data, $classname, $format, $context);
        $this->wireEm->decDebugMode();
        return $data;
    }

    /****************************************************************************************************/
    /** NORMALIZER ENTITY                                                                               */
    /****************************************************************************************************/

    public function normalizeEntity(
        BaseEntityInterface $entity,
        ?string $format = null,
        ?array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return $this->normalize($entity, $format, $context);
    }

    public function denormalizeEntity(
        array|EntityContainerInterface $data,
        string $classname,
        ?string $format = null,
        ?array $context = []
    ): BaseEntityInterface {
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        $context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] ??= true;
        if(!($data instanceof EntityContainerInterface)) {
            $data = new EntityContainerInterface($this, $classname, $data, $context);
        }
        $this->wireEm->incDebugMode();
        $entity = $this->getSerializer()->denormalize($data, $classname);
        $this->wireEm->decDebugMode();
        $this->addCreated($entity);
        return $entity;
    }


    /****************************************************************************************************/
    /** SERIALIZER                                                                                      */
    /****************************************************************************************************/

    public function serialize(
        mixed $data,
        string $format,
        ?array $context = [],
        ?bool $convertToArrayList = false
    ): string {
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        $context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ??= false;
        $context[AbstractObjectNormalizer::CIRCULAR_REFERENCE_LIMIT] ??= static::CIRCULAR_REFERENCE_LIMIT;
        // $context[AbstractObjectNormalizer::MAX_DEPTH_HANDLER] ??= $this->getMaxDepthHandler();
        if ($data instanceof Collection) $data = $data->toArray();
        if ($convertToArrayList && is_array($data) && !array_is_list($data)) $data = array_values($data); // for React, can not be object, but array
        return $this->getSerializer()->serialize($data, $format, $context);
    }

    public function deserialize(
        string $data,
        string $type,
        string $format,
        ?array $context = []
    ): mixed {
        $context[AbstractObjectNormalizer::ENABLE_MAX_DEPTH] ??= true;
        $context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] ??= true;
        $this->wireEm->incDebugMode();
        $data = $this->getSerializer()->deserialize($data, $type, $format, $context);
        $this->wireEm->decDebugMode();
        return $data;
    }


    /****************************************************************************************************/
    /** GENERATOR                                                                                       */
    /****************************************************************************************************/


    private function getInstantiableEntityNames(): array
    {
        return $this->entityNames ??= $this->wireEm->getEntityNames(true, false, true);
    }

    private function getEntityClassname(
        string $shortname
    ): string|null
    {
        $this->getInstantiableEntityNames();
        if(in_array($shortname, $this->entityNames)) return array_search($shortname, $this->entityNames);
        if(array_key_exists($shortname, $this->entityNames)) return $shortname;
        return null;
    }

    private function getEntityShortname(
        string $classname
    ): string|null
    {
        $this->getInstantiableEntityNames();
        if(in_array($classname, $this->entityNames)) return $classname;
        if(array_key_exists($classname, $this->entityNames)) return $this->entityNames[$classname];
        return null;
    }

    /**
     * Define the current path for the generator
     * 
     * @param string|null $path
     * @return string
     */
    public function setCurrentPath(
        ?string $path = null
    ): void
    {
        $path ??= $this->appWire->getProjectDir(static::DEFAULT_DATA_PATH);
        $currentPath = new SplFileInfo($path);
        if (!$currentPath->isDir()) {
            throw new Exception(vsprintf('Error %s line %d: path %s is not a directory!', [__METHOD__, __LINE__, $path]));
        }
        if (!$currentPath->isReadable()) {
            throw new Exception(vsprintf('Error %s line %d: path %s is not readable!', [__METHOD__, __LINE__, $path]));
        }
        if(!isset($this->currentPath) || ($currentPath->getRealPath() !== $this->currentPath->getRealPath())) {
            $this->currentPath = $currentPath;
            if(isset($this->catalogue)) $this->createIdentityCatalog(true);
        }
    }

    /**
     * Get the current path for the generator
     * 
     * @return SplFileInfo
     */
    public function getCurrentPath(): SplFileInfo
    {
        return $this->currentPath;
    }

    // private function regularizePath(
    //     null|string|SplFileInfo &$path = null,
    //     bool $dir_wanted = false
    // ): bool
    // {
    //     if(empty($path)) {
    //         $path = $this->getCurrentPath();
    //     }
    //     if(is_string($path)) {
    //         $test_path = $this->appWire->getProjectDir($path);
    //         if(!file_exists($test_path)) {
    //             $test_path = $this->appWire->getProjectDir(static::DEFAULT_DATA_PATH.ltrim($path, '/'));
    //         }
    //         if(!file_exists($test_path)) {
    //             $path = false;
    //         }
    //         $path = file_exists($test_path) ? new SplFileInfo($test_path) : false;
    //     }
    //     if($path && $path->isLink()) {
    //         $path = $path->getRealPath() ? new SplFileInfo($path->getRealPath()) : false;
    //     }
    //     // Controls
    //     if($path instanceof SplFileInfo) {
    //         if ($dir_wanted && !$path->isDir()) {
    //             $path = new SplFileInfo($path->getPath());
    //         }
    //         if(
    //             (!$path->isReadable()) || 
    //             ($dir_wanted && !$path->isDir()) || 
    //             ($path->isFile() && !in_array($path->getExtension(), ['yaml', 'yml']))
    //         ) {
    //             $path = false;
    //         }
    //     }
    //     return $path instanceof SplFileInfo;
    // }

    /**
     * Check if a file is a valid YAML file
     * - if $checkClassname is set, it will check if the file contains the class
     * 
     * @param SplFileInfo $file
     * @param string|null $checkClassname
     * @return bool
     */
    private function isValidYamlFile(
        SplFileInfo $file,
        ?string $checkClassname = null
    ): bool
    {
        if(!($file->isReadable() && $file->isFile() && in_array($file->getExtension(), ['yaml', 'yml']))) {
            return false;
        }
        if(empty($checkClassname)) return true;
        $classname = $this->getEntityClassname($checkClassname);
        if($classname) {
            $data = Files::readYamlFile($file);
            return $data['entity'] === $classname;
        }
        $this->logger->error(vsprintf('Error %s line %d: class %s not found, can not check if content of the file is valid!', [__METHOD__, __LINE__, $checkClassname]));
        return false;
    }

    /**
     * Find all YAML files in current path
     * 
     * @return array
     */
    private function findYamlFiles(array $filenamesOrClassnames = []): array
    {

        if(empty($filenamesOrClassnames)) {
            $files = Files::listFiles(
                $this->getCurrentPath(),
                fn(SplFileInfo $file) => $this->isValidYamlFile($file)
            );
        } else {
            $files = [];
            foreach ($filenamesOrClassnames as $filenameOrClassname) {
                if($file = $this->findYamlFile($filenameOrClassname)) {
                    $files[] = $file;
                } else {
                    $this->logger->warning(vsprintf('Error %s line %d: file with parameter %s not found!', [__METHOD__, __LINE__, $filenameOrClassname]));
                }
            }
        }
        return $files;
    }

    /**
     * Find one YAML file in current path
     * - $filenameOrClassname can be a filename, classname or shortname
     * 
     * @param string $filenameOrClassname
     * @return SplFileInfo|false
     */
    private function findYamlFile(
        string $filenameOrClassname
    ): SplFileInfo|false
    {
        $file = false;
        if($shortname = $this->getEntityShortname($filenameOrClassname)) {
            // Is classname/shortname
            $file = $this->getCurrentPath().DIRECTORY_SEPARATOR.$shortname;
            $file = new SplFileInfo(file_exists($file.'.yaml') ? $file.'.yaml' : $file.'.yml');
        } else if(file_exists($this->getCurrentPath().DIRECTORY_SEPARATOR.$filenameOrClassname)) {
            // Is filename
            $file = new SplFileInfo($this->getCurrentPath().DIRECTORY_SEPARATOR.$filenameOrClassname);
        }
        return $file instanceof SplFileInfo && $this->isValidYamlFile($file, $shortname) ? $file : false;
    }

    /**
     * Get raw YAML data from file
     * - raw data is not compiled
     * 
     * @param SplFileInfo $file
     * @return null|array
     */
    private function loadRawYamlData(
        SplFileInfo $file
    ): ?array
    {
        $path = $file->getRealPath();
        if(!$path) {
            throw new Exception(vsprintf('Error %s line %d: file %s not found!', [__METHOD__, __LINE__, $path]));
        }
        if(!isset($this->filecontents[$path])) {
            $this->wireEm->surveyRecursion->survey(__METHOD__.'@'.$path, 2);
            $this->filecontents[$path] ??= Files::readYamlFile($file);
        }
        return $this->filecontents[$path];
    }

    private function createIdentityCatalog(bool $force_update = false): void
    {
        if(!isset($this->catalogue) || $force_update) {
            $this->wireEm->surveyRecursion->survey(__METHOD__, 5, vsprintf('Error %s line %d: max recursion %d limit reached while creating identity catalogue for path %s (force update: %s)', [__METHOD__, __LINE__, 5, $this->getCurrentPath()->getRealPath(), json_encode($force_update)]));
            $this->catalogue = ['uname' => [], 'euid' => []];
            foreach ($this->findYamlFiles() as $file) {
                $rawData = $this->loadRawYamlData($file);
                if(!empty($rawData)) {
                    foreach ($rawData['items'] as $key => $value) {
                        // If key is a valid EUID or Uname, add it to the catalogue
                        if(Encoders::isEuidFormatValid($key)) {
                            $this->catalogue['euid'][$key] = $rawData['entity'];
                        } else if(Encoders::isUnameFormatValid($key)) {
                            $this->catalogue['uname'][$key] = $rawData['entity'];
                        }
                        // If value is a valid EUID or Uname, add it to the catalogue
                        if(is_array($value) && Encoders::isEuidFormatValid($value['euid'] ?? null)) {
                            $this->catalogue['euid'][$value['euid']] = $rawData['entity'];
                        } else if(is_array($value) && Encoders::isUnameFormatValid($value['uname'] ?? null)) {
                            $this->catalogue['uname'][$value['uname']] = $rawData['entity'];
                        } else if(is_array($value) && Encoders::isUnameFormatValid($value['uname']['uname'] ?? null)) {
                            $this->catalogue['uname'][$value['uname']['uname']] = $rawData['entity'];
                        } else if(is_array($value) && Encoders::isUnameFormatValid($value['uname']['id'] ?? null)) {
                            $this->catalogue['uname'][$value['uname']['id']] = $rawData['entity'];
                        }
                    }
                }
            }
            // dump($this->catalogue);
        }
    }

    public function tryFindCatalogueClassname(string $uname): ?string
    {
        $this->createIdentityCatalog(false);
        return $this->catalogue['uname'][$uname] ?? $this->catalogue['euid'][$uname] ?? null;
    }

    // public function tryFindClassnameOfUname(
    //     string|array $uname,
    //     array $availableClassnames = [],
    //     ?string $defaultClassname = null
    // ): ?string
    // {
    //     if(is_string($uname)) {
    //         $this->wireEm->surveyRecursion->survey(__METHOD__.'@'.$uname, 10);
    //     }
    //     if(is_array($uname)) {
    //         return $this->tryFindClassnameOfUname($uname['uname'], $availableClassnames, $defaultClassname);
    //     }
    //     if(!Encoders::isUnameFormatValid($uname)) {
    //         throw new Exception(vsprintf('Error %s line %d: Uname %s is not valid!', [__METHOD__, __LINE__, $uname]));            
    //     }
    //     if($entity = $this->findEntityByUname($uname)) {
    //         // Try in createds, then in database
    //         $found = $entity->getClassname();
    //     } else {
    //         // Try in catalogue
    //         $found = $this->tryFindCatalogueClassname($uname);
    //     }
    //     if($found && !empty($availableClassnames)) {
    //         foreach ($availableClassnames as $classname) {
    //             if(is_a($found, $classname, true)) return $found;
    //         }
    //     }
    //     // dump($uname, $found, $availableClassnames, $defaultClassname);
    //     return $found ?? $defaultClassname;
    // }

    /**
     * Get compiled YAML data from a path directory
     * - compiled data contains all data for denormalization
     * - if empty $filenamesOrClassnames, it will return all YAML files in the current path
     * 
     * @param array $filenamesOrClassnames = []
     * @return array
     */
    public function getYamlData(
        array $filenamesOrClassnames = [],
        int $mode_report = 0
    ): array
    {
        $files = $this->findYamlFiles($filenamesOrClassnames);
        $data = [];
        foreach ($files as $file) {
            $rawData = $this->loadRawYamlData($file);
            if(!empty($rawData)) {
                static::UnhumanizeEntitiesYamlData($rawData['items']);
                foreach ($rawData['items'] as $values) {
                    $this->logger->debug(vsprintf('Debug ***** file %s, entity %s *****', [$file->getRealPath(), $rawData['entity']]));
                    $new_entityContainer = new EntityContainer($this, $rawData['entity'], $values, $rawData['context'] ?? []);
                    if($new_entityContainer->isValid()) {
                        $data[$rawData['order']] ??= [];
                        $data[$rawData['order']][] = $new_entityContainer;
                    } else {
                        $message = vsprintf('Error %s line %d: entity %s is not valid!%s%s', [__METHOD__, __LINE__, $rawData['entity'], PHP_EOL, $new_entityContainer->getMessagesAsString(false)]);
                        $this->logger->error($message);
                        // throw new Exception($message);
                    }
                }
            }
        }
        // Sort data
        ksort($data);
        // $data = array_values($data);
        $list = [];
        foreach ($data as $index => $byclasses) {
            if(count($byclasses) > 0) {
                $index = $byclasses[0]->getClassname();
                $list[$index] = [];
                foreach ($byclasses as $values) {
                    switch ($mode_report) {
                        case 1:
                            $list[$index][] = $values->getRawdata();
                            break;
                        case 2:
                            $list[$index][] = $values->getRawdata(true);
                            break;
                        case 3:
                            $list[$index][] = $values->getCompiledData();
                            break;
                        default:
                            // Default (0): for denormalization
                            $list[$index][] = $values;
                    }
                }
            }
        }
        return $list;
    }

    public static function HumanizeEntitiesYamlData(array &$data): void
    {
        $new_data = [];
        foreach($data as $key => $values) {
            if(!is_int($key)) {
                throw new Exception(vsprintf('Error %s line %d: key %s is not an integer!%sHas this data already been humanized?', [__METHOD__, __LINE__, $key, PHP_EOL]));
            }
            if(isset($values['uname'])) {
                $uname = $values['uname']['uname'] ?? $values['uname'];
                unset($values['uname']);
                $new_data[$uname] = $values;
            } else if(isset($values['euid'])) {
                $euid = $values['euid'];
                unset($values['euid']);
                $new_data[$euid] = $values;
            } else {
                $new_data[$key] = $values;
            }
        }
        // dump($new_data);
        $data = $new_data;
    }

    /**
     * Transform a unamed list of entities to a array list with uname array element
     * - if the key is a valid uname or euid, it will be added to the values as 'uname' or 'euid' key with the value
     * 
     * @param array $data
     * @return void
     */
    public static function UnhumanizeEntitiesYamlData(array &$data): void
    {
        array_walk(
            $data,
            function(&$values, $unameOrEuid) {
                if (Encoders::isUnameFormatValid($values)) {
                    $values = ['uname' => ['uname' => $values, 'classname' => Uname::class, 'shortname' => Objects::getShortname(Uname::class)]];
                } else if (Encoders::isEuidFormatValid($values)) {
                    $values = ['euid' => $values];
                }
                if (Encoders::isUnameFormatValid($unameOrEuid)) {
                    $values = array_merge(['uname' => ['uname' => $unameOrEuid, 'classname' => Uname::class, 'shortname' => Objects::getShortname(Uname::class)]], $values);
                } else if (Encoders::isEuidFormatValid($unameOrEuid)) {
                    $values = array_merge(['euid' => $unameOrEuid], $values);
                }
            }
        );
        $data = array_values($data);
    }

    // private static function sortData(array &$data): void
    // {
    //     uasort($data, function ($a, $b) {
    //         if ($a['order'] == $b['order']) return 0;
    //         return $a['order'] < $b['order'] ? -1 : 1;
    //     });
    // }

    /**
     * Get a report data of all YAML files in a directory
     * 
     * @param array $filenamesOrClassnames = []
     * @return array|false
     */
    public function getReport(
        array|string $filenamesOrClassnames = [],
        int $mode_report = 2
    ): array
    {
        return $this->getYamlData((array)$filenamesOrClassnames, $mode_report);
    }

    /**
     * Generate entities of a class from a YAML file
     * - finds the file by classname or shortname from the current path
     * - please, define the path before calling this method if needed with setCurrentPath() method
     * 
     * @param string $classname
     * @param bool $replace
     * @param SymfonyStyle|null $io
     * @param bool $flush
     * @return OpresultInterface
     */
    public function generateEntitiesFromClass(
        string $classname,
        bool $replace = false,
        ?SymfonyStyle $io = null,
        bool $flush = true
    ): OpresultInterface {
        $result = new Opresult();
        if($classname = $this->getEntityClassname($classname)) {
            foreach ($this->getYamlData([$classname]) as $data) {
                // Embed results
                $result->addOpresult($this->generateEntities($data['entity'], $data['items'], $replace, $io, $flush));
            }
        } else {
            $result->addDanger(vsprintf('La classe %s n\'est pas une entité instantiable ou valide', [$classname]));
            // $result->addUndone(vsprintf('La class d\'entité %s n\'a donné aucun résultat', [$classname]));
        }
        return $result;
    }

    public function generateEntities(
        $classname,
        array $items,
        bool $replace = false,
        ?SymfonyStyle $io = null,
        bool $flush = true
    ): OpresultInterface {
        if (!$this->wireEm->entityExists($classname, true, true)) {
            throw new Exception(vsprintf('La classe %s n\'est pas une entité instantiable ou valide', [$classname]));
        }
        $opresult = new Opresult();
        if ($io) $io->writeln(vsprintf('- Génération de <info>%s</info> entités pour <info>%s</info>', [count($items), $classname]));
        $context = [
            EntityContainerInterface::CONTEXT_MAIN_GROUP => NormalizerServiceInterface::MAIN_GROUP,
            EntityContainerInterface::CONTEXT_CREATE_ONLY => false,
            EntityContainerInterface::CONTEXT_AS_MODEL => false,
        ];
        $progress = $io ? $io->progressIterate($items) : $items;
        $count = 0;
        foreach ($progress as $data) {
            /** @var BaseEntityInterface $entity */
            $entity = $this->denormalizeEntity($data, $classname, context: $context);
            if(!$entity) {
                $opresult->addDanger(vsprintf('Erreur de dénormalisation de l\'entité %s avec les données %s', [$classname, json_encode($data)]));
                continue;
            }
            // dd($this->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => static::getNormalizeGroups($entity, 'debug')]));
            if (!$replace && $entity->getSelfState()->isLoaded()
                // && $entity->getEmbededStatus()->isContained()
            ) {
                $this->wireEm->getEm()->detach($entity);
                $opresult->addWarning(vsprintf('L\'entité %s existe déjà, elle ne sera pas modifiée.', [Objects::toDebugString($entity)]));
                continue;
            }
            // Validation
            $errors = $this->validator->validate($entity, null, $entity->__selfstate->isNew() ? ['persist'] : ['update']);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                $opresult->addDanger(vsprintf('Erreur de validation de l\'entité %s :%s- %s', [Objects::toDebugString($entity), PHP_EOL, implode(PHP_EOL.'- ', $messages)]));
                $opresult->addData($entity->getUnameThenEuid(), $entity);
                // dd($this->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => static::getNormalizeGroups($entity, 'debug')]));
                continue;
            }
            if($entity->getSelfState()->isNew()) {
                $this->wireEm->getEm()->persist($entity);
                $opresult->addSuccess(vsprintf('L\'entité %s a été CRÉÉE', [Objects::toDebugString($entity)]));
            } else {
                $action = $this->wireEm->getEm()->getUnitOfWork()->isScheduledForUpdate($entity) ? 'MODIFIÉE' : 'NON MODIFIÉE';
                $opresult->addSuccess(vsprintf('L\'entité %s a été %s', [Objects::toDebugString($entity), $action]));
            }
            // $opresult->addData($entity->getUnameThenEuid(), $this->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => static::getNormalizeGroups($entity, 'debug')]));
            $opresult->addData($entity->getUnameThenEuid(), $entity);
            $count++;
        }
        if ($count > 0 && $flush) {
            // dd($opresult->getData());
            $this->wireEm->getEm()->flush();
        }
        foreach ($opresult->getData() as $euid => $entity) {
            // $opresult->addData($euid, $this->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => static::getNormalizeGroups($entity, 'debug')]));
        }
        return $opresult;
    }


    /****************************************************************************************************/
    /** REQUESTS ON CREATED ENTITIES                                                                    */
    /****************************************************************************************************/

    public function findEntityByEuid(
        string $euid
    ): ?BaseEntityInterface
    {
        return ($entity = $this->findCreated($euid))
            ? $entity
            : $this->wireEm->findEntityByEuid($euid);
    }

    public function findEntityByUname(
        string $uname
    ): ?BaseEntityInterface
    {
        return ($entity = $this->findCreated($uname))
            ? $entity
            : $this->wireEm->findEntityByUname($uname);
    }

    public function getClassnameByUname(
        string $uname
    ): ?string
    {
        $result = ($entity = $this->findCreated($uname))
            ? $entity->getClassname()
            : $this->wireEm->getClassnameByUname($uname);
        return $result ? $result : $this->tryFindCatalogueClassname($uname);
    }

    public function getClassnameByEuidOrUname(
        string $euidOrUname
    ): ?string
    {
        $result = ($entity = $this->findCreated($euidOrUname))
            ? $entity->getClassname()
            : $this->wireEm->getClassnameByEuidOrUname($euidOrUname);
        return $result ? $result : $this->tryFindCatalogueClassname($euidOrUname);
    }

    public function getRelationMapper(string $classname): RelationMapperInterface
    {
        return $this->relationMappers[$classname] ??= new RelationMapper($classname, $this->wireEm);
    }


}
