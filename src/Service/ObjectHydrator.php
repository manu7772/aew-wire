<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\ObjectHydratorInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Files;
use Exception;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
// PHP
use SplFileInfo;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsAlias(ObjectHydratorInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class ObjectHydrator implements ObjectHydratorInterface
{
    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wire_em,
        public readonly ValidatorInterface $validator
    ) {
    }

    private function regularizePath(
        string|SplFileInfo &$path,
        bool $dir_wanted = false
    ): void
    {
        $path = $path instanceof SplFileInfo ? (string)$path->getPathname() : $path;
        if(!file_exists($path)) {
            $path = $this->appWire->getProjectDir($path);
        }
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

    public function generateEntities(
        $classname,
        array $items,
        bool $replace = false,
        ?SymfonyStyle $io = null
    ): OpresultInterface
    {
        $em = $this->wire_em->getEm();
        $opresult = new Opresult();
        if($io) $io->writeln(vsprintf('- Génération de <info>%s</info> entités pour <info>%s</info>', [count($items), $classname]));
        $progress = $io ? $io->progressIterate($items) : $items;
        foreach ($progress as $uname => $item_data) {
            // Add Uname if not present
            // $item_data['uname'] ??= $uname;
            // Create entity
            $entity = $this->wire_em->createEntity($classname, $item_data);
            $errors = $this->validator->validate($entity);
            if(count($errors) > 0) {
                $opresult->addDanger(vsprintf('Erreur de validation de l\'entité %s %s : %s', [$entity->getShortname(), $uname, $errors[0]->getMessage()]));
                continue;
            }
            $em->persist($entity);
            $opresult->addSuccess(vsprintf('L\'entité %s %s a été enregistrée', [$entity->getShortname(), $uname]));
            sleep(1);
        }
        // $em->flush();
        return $opresult;
    }

}