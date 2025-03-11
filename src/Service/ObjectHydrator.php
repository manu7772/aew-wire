<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Serializer\EntityDenormalizer;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\ObjectHydratorInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Objects;
use Exception;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
// PHP
use SplFileInfo;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsAlias(ObjectHydratorInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class ObjectHydrator implements ObjectHydratorInterface
{
    public const DEFAULT_DATA_PATH = 'src/DataBasics/data/';

    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wire_em,
        public readonly ValidatorInterface $validator,
        public readonly NormalizerServiceInterface $normalizer,
    ) {
    }

    /**
     * Regularize a path to a file or directory
     * @param string|SplFileInfo $path of entity classname/shortname
     * @param bool $dir_wanted
     */
    private function regularizePath(
        string|SplFileInfo &$path,
        bool $dir_wanted = false
    ): void
    {
        $names = $this->wire_em->getEntityNames(true, false, true);
        if(in_array($path, $names) || array_key_exists($path, $names)) {
            // Classname or shortname given
            if(array_key_exists($path, $names)) {
                $path = Objects::getShortname($path);
            }
            $path = $this->appWire->getProjectDir(static::DEFAULT_DATA_PATH.ltrim($path, '/'));
            $path = file_exists($path.'.yaml') ? $path.'.yaml' : $path.'.yml';
        } else if($path instanceof SplFileInfo) {
            // SplFileInfo given
            $path = $path->getPathname();
        } else {
            // string path given
            $m_path = $path;
            if(!file_exists($path)) {
                // try add project dir
                $path = $this->appWire->getProjectDir($m_path);
            }
            if(!file_exists($path)) {
                // Try add project dir + default path
                $path = $this->appWire->getProjectDir(static::DEFAULT_DATA_PATH.ltrim($m_path, '/'));
            }
        }
        // $m_path = $path = preg_replace('/(\.(ya?ml)*)*$/', '', $path).'.yaml';
        if(file_exists($path)) {
            if($dir_wanted && !is_dir($path)) {
                $path = false;
                return;
            }
            $path = is_readable($path) ? $path : false;
            return;
        }
        $path = false;
    }

    public function getAppWire(): AppWireServiceInterface
    {
        return $this->appWire;
    }

    public function getWireEntityManager(): WireEntityManagerInterface
    {
        return $this->wire_em;
    }

    public function findPathYamlFiles(
        string $path
    ): array|false
    {
        $this->regularizePath($path, true);
        return $path
            ? Files::listFiles($path, fn(SplFileInfo $file) => $file->isReadable() && in_array($file->getExtension(), ['yaml','yml']))
            : false;
    }

    public function getPathYamlData(
        string $path
    ): array|false
    {
        if($files = $this->findPathYamlFiles($path)) {
            $data = [];
            foreach($files as $file) {
                if($yaml = $this->getYamlData($file)) {
                    $data[$file->getFilename()] = $yaml;
                }
            }
            uasort($data, function($a, $b) {
                if($a['order'] == $b['order']) return 0;
                return $a['order'] < $b['order'] ? -1 : 1;
            });
            return $data;
        }
        return false;
    }

    public function getYamlData(
        string|SplFileInfo $file
    ): array|false
    {
        $this->regularizePath($file, false);
        if($file) {
            $data = Files::readYamlFile($file);
            return $data['data'] ?? false;
        }
        return false;
    }

    public function generateEntitiesFromClass(
        string $classname,
        bool $replace = false,
        ?SymfonyStyle $io = null
    ): OpresultInterface
    {
        $data = $this->getYamlData($classname);
        if($data) {
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
    ): OpresultInterface
    {
        $data = $this->getYamlData($filename);
        if($data) {
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
    ): OpresultInterface
    {
        if(!$this->wire_em->entityExists($classname)) {
            throw new Exception(vsprintf('La classe %s n\'est pas une entité valide', [$classname]));
        }
        $em = $this->wire_em->getEm();
        $service = $this->wire_em->getEntityService($classname);
        $opresult = new Opresult();
        if($io) $io->writeln(vsprintf('- Génération de <info>%s</info> entités pour <info>%s</info>', [count($items), $classname]));
        $progress = $io ? $io->progressIterate($items) : $items;
        foreach ($progress as $uname => $item_data) {
            // Add Uname if not present
            if(is_string($uname)) {
                $item_data['uname'] ??= $uname;
            }
            $context = [];
            // Check if entity already exists
            if($entity = $this->wire_em->findEntityByUname($item_data['uname'])) {
                if($replace) {
                    $context = [AbstractNormalizer::OBJECT_TO_POPULATE => $entity];
                } else {
                    $opresult->addWarning(vsprintf('L\'entité %s %s [%s] existe déjà', [$entity->getShortname(), $entity->__toString(), $uname]));
                    continue;
                }
            }
            if($service) {
                // Create entity with specific service
                $entity = $service->createEntity($item_data, $context);
                // Check entity
                $service->checkEntity($entity);
            } else {
                // Create entity with generic service
                $entity = $this->wire_em->createEntity($classname, $item_data, $context);
                // Check entity
                $this->wire_em->checkEntityBase($entity);
            }
            $opresult->addData($entity->getUnameThenEuid(), $this->normalizer->normalizeEntity($entity, context: ['groups' => EntityDenormalizer::getNormalizeGroups($classname, type: 'debug')['normalize']]));
            $errors = $this->validator->validate($entity);
            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                $opresult->addDanger(vsprintf('Erreur de validation de l\'entité %s %s :%s- %s', [$entity->getShortname(), $uname, PHP_EOL, implode(PHP_EOL.'- ', $messages)]));
                continue;
            }
            $em->persist($entity);
            $opresult->addSuccess(vsprintf('L\'entité %s %s [%s] a été enregistrée', [$entity->getShortname(), $entity->__toString(), $uname]));
            // sleep(1);
        }
        // dd($opresult->getData());
        // $em->flush();
        return $opresult;
    }

}