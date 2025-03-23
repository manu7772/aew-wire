<?php

namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\NormalizeDataContainer;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
// PHP
use ArrayObject;
use Exception;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Normalizer service
 * @see https://symfony.com/doc/current/serializer.html
 */
#[AsAlias(NormalizerServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class NormalizerService implements NormalizerServiceInterface
{
    use TraitBaseService;

    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wireEm,
        public readonly SerializerInterface $serializer,
        public readonly ValidatorInterface $validator
    ) {}

    public function getSerializer(): SerializerInterface & NormalizerInterface & DenormalizerInterface
    {
        return $this->serializer;
    }


    /****************************************************************************************************/
    /** CLEANING / PREPARING                                                                            */
    /****************************************************************************************************/

    /**
     * Get (de)normalization groups for a class
     * returns array of 2 named elements:
     *      - ["normalize" => <normalization groups>]
     *      - ["denormalize" => <denormalization groups>]
     * @param string|WireEntityInterface $class
     * @param string $type
     * @return array
     */
    private static function _getGroups(
        string|WireEntityInterface $class,
        string $type
    ): array {
        if ($class instanceof WireEntityInterface) {
            $class = $class->getClassname();
        }
        if (class_exists($class)) {
            $shortname = Objects::getShortname($class, true);
        } else {
            throw new Exception(vsprintf('Error %s line %d: Class %s not found or not instance of %s!', [__METHOD__, __LINE__, $class, WireEntityInterface::class]));
        }
        $types = static::NORMALIZATION_GROUPS[$type] ?? static::NORMALIZATION_GROUPS['_default'];
        if (empty($type) || $type === '_default') $type = static::MAIN_GROUP;
        $groups = [
            'normalize' => [],
            'denormalize' => [],
        ];
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
        return $groups;
    }

    /**
     * Get normalization groups for a class
     * @param string|WireEntityInterface $class
     * @param string $type
     * @return array
     */
    public static function getNormalizeGroups(
        string|WireEntityInterface $class,
        ?string $type = null, // ['hydrate','model','clone','debug'...]
    ): array {
        return static::_getGroups($class, $type)['normalize'];
    }

    /**
     * Get denormalization groups for a class
     * @param string|WireEntityInterface $class
     * @param string $type
     * @return array
     */
    public static function getDenormalizeGroups(
        string|WireEntityInterface $class,
        ?string $type = null, // ['hydrate','model','clone','debug'...]
    ): array {
        return static::_getGroups($class, $type)['denormalize'];
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
        if ($data instanceof Collection) $data = $data->toArray();
        if ($convertToArrayList && is_array($data) && !array_is_list($data)) $data = array_values($data);
        return $this->getSerializer()->normalize($data, $format, $context);
    }

    public function denormalize(
        mixed $data,
        string $classname,
        ?string $format = null,
        ?array $context = []
    ): mixed {
        if (is_a($classname, WireEntityInterface::class, true)) {
            // throw new Exception(vsprintf('Error %s line %d: please, use method denormalizeEntity() to denormalize entities!', [__METHOD__, __LINE__]));
            return $this->denormalizeEntity($data, $classname, $format, $context);
        }
        return $this->getSerializer()->denormalize($data, $classname, $format, $context);
    }

    /****************************************************************************************************/
    /** NORMALIZER ENTITY                                                                               */
    /****************************************************************************************************/

    public function normalizeEntity(
        WireEntityInterface $entity,
        ?string $format = null,
        ?array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return $this->normalize($entity, $format, $context);
    }

    public function denormalizeEntity(
        array|NormalizeDataContainer $data,
        string $classname,
        ?string $format = null,
        ?array $context = []
    ): WireEntityInterface {
        if(!($data instanceof NormalizeDataContainer)) {
            $data = new NormalizeDataContainer($this->wireEm, $classname, $data, $context);
        }
        $entity = $this->getSerializer()->denormalize($data, $classname, $format, $data->getContext());
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
        return $this->getSerializer()->deserialize($data, $type, $format, $context);
    }


    /****************************************************************************************************/
    /** GENERATOR                                                                                       */
    /****************************************************************************************************/

    /**
     * Regularize a path to a file or directory
     * @param string|SplFileInfo $path of entity classname/shortname
     * @param bool $dir_wanted
     */
    private function regularizePath(
        string|SplFileInfo &$path,
        bool $dir_wanted = false
    ): void {
        $names = $this->wireEm->getEntityNames(true, false, true);
        if ($path instanceof SplFileInfo) {
            // SplFileInfo given
            $path = $path->getPathname();
        } else if (in_array($path, $names) || array_key_exists($path, $names)) {
            // Classname or shortname given
            if (array_key_exists($path, $names)) {
                $path = Objects::getShortname($path);
            }
            $path = $this->appWire->getProjectDir(static::DEFAULT_DATA_PATH . ltrim($path, '/'));
            $path = file_exists($path . '.yaml') ? $path . '.yaml' : $path . '.yml';
        } else {
            // string path given
            $m_path = $path;
            if (!file_exists($path)) {
                // try add project dir
                $path = $this->appWire->getProjectDir($m_path);
            }
            if (!file_exists($path)) {
                // Try add project dir + default path
                $path = $this->appWire->getProjectDir(static::DEFAULT_DATA_PATH . ltrim($m_path, '/'));
            }
        }
        // $m_path = $path = preg_replace('/(\.(ya?ml)*)*$/', '', $path).'.yaml';
        if (file_exists($path)) {
            if ($dir_wanted && !is_dir($path)) {
                $path = false;
                return;
            }
            $path = is_readable($path) ? $path : false;
            return;
        }
        $path = false;
    }

    public function findPathYamlFiles(
        string $path
    ): array|false {
        $this->regularizePath($path, true);
        return $path
            ? Files::listFiles($path, fn(SplFileInfo $file) => $file->isReadable() && in_array($file->getExtension(), ['yaml', 'yml']))
            : false;
    }

    public function getPathYamlData(
        string $path
    ): array|false {
        if ($files = $this->findPathYamlFiles($path)) {
            $data = [];
            foreach ($files as $file) {
                if ($yaml = $this->getYamlData($file)) {
                    $data[$file->getFilename()] = $yaml;
                }
            }
            uasort($data, function ($a, $b) {
                if ($a['order'] == $b['order']) return 0;
                return $a['order'] < $b['order'] ? -1 : 1;
            });
            return $data;
        }
        return false;
    }

    public function getYamlData(
        string|SplFileInfo $file
    ): array|false {
        $this->regularizePath($file, false);
        if ($file) {
            $data = Files::readYamlFile($file);
            return $data['data'] ?? false;
        }
        return false;
    }

    public function generateEntitiesFromClass(
        string $classname,
        bool $replace = false,
        ?SymfonyStyle $io = null
    ): OpresultInterface {
        $data = $this->getYamlData($classname);
        if ($data) {
            return $this->generateEntities($data['entity'], $data['items'], $replace, $io);
        }
        $result = new Opresult();
        $result->addUndone(vsprintf('La class d\'entité %s n\'a donné aucun résultat', [$classname]));
        return $result;
    }

    public function generateEntitiesFromFile(
        string $filename,
        bool $replace = false,
        ?SymfonyStyle $io = null
    ): OpresultInterface {
        $data = $this->getYamlData($filename);
        if ($data) {
            return $this->generateEntities($data['entity'], $data['items'], $replace, $io);
        }
        $result = new Opresult();
        $result->addUndone(vsprintf('Le fichier %s n\'a donné aucun résultat', [$filename]));
        return $result;
    }

    public function generateEntities(
        $classname,
        array $items,
        bool $replace = false,
        ?SymfonyStyle $io = null
    ): OpresultInterface {
        if (!$this->wireEm->entityExists($classname, false, true)) {
            throw new Exception(vsprintf('La classe %s n\'est pas une entité instantiable ou valide', [$classname]));
        }
        $opresult = new Opresult();
        if ($io) $io->writeln(vsprintf('- Génération de <info>%s</info> entités pour <info>%s</info>', [count($items), $classname]));
        $progress = $io ? $io->progressIterate($items) : $items;
        $count = 0;
        foreach ($progress as $uname => $data) {
            if(is_string($uname)) {
                $data['uname'] ??= $uname;
            }
            // dd($data);
            $data = new NormalizeDataContainer($this->wireEm, $classname, $data, create_only: false);
            /** @var WireEntityInterface $entity */
            $entity = $this->denormalizeEntity($data, $classname);
            // dd($this->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => static::getNormalizeGroups($entity, 'debug')]));
            if (!$replace
                && $entity->getSelfState()->isLoaded()
                // && $entity->getEmbededStatus()->isContained()
            ) {
                $opresult->addWarning(vsprintf('L\'entité %s %s [%s] existe déjà, elle ne sera pas modifiée.', [$entity->getShortname(), $entity->__toString(), $uname]));
                continue;
            }
            // Validation
            $errors = $this->validator->validate($entity, null, $entity->__selfstate->isNew() ? ['persist'] : ['update']);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                $opresult->addDanger(vsprintf('Erreur de validation de l\'entité %s %s :%s- %s', [$entity->getShortname(), $uname, PHP_EOL, implode(PHP_EOL . '- ', $messages)]));
                $opresult->addData($entity->getUnameThenEuid(), $entity);
                // dd($this->normalizeEntity($entity, context: [AbstractNormalizer::GROUPS => static::getNormalizeGroups($entity, 'debug')]));
                continue;
            }
            $this->wireEm->getEm()->persist($entity);
            $opresult->addSuccess(vsprintf('L\'entité %s %s [%s] a été enregistrée', [$entity->getShortname(), $entity->__toString(), $uname]));
            $opresult->addData($entity->getUnameThenEuid(), $entity);
            // sleep(1);
            $count++;
        }
        if ($count > 0) {
            // $this->wireEm->getEm()->flush();
        }
        return $opresult;
    }
}
