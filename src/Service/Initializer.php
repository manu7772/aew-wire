<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\AequationWireBundle;
use Aequation\WireBundle\Component\EntityCreator;
use Aequation\WireBundle\Service\interface\ExpressionLanguageServiceInterface;
use Aequation\WireBundle\Service\interface\InitializerInterface;
use Aequation\WireBundle\Tools\Iterables;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
// PHP
use Exception;
use Twig\Environment;

#[AsAlias(InitializerInterface::class, public: false)]
#[Autoconfigure(autowire: true, lazy: true)]
class Initializer extends BaseService implements InitializerInterface
{

    private array $config_files = [];
    private string $project_dir;
    // private string $log_dir;
    private string $package_dir;

    public function __construct(
        private KernelInterface $kernel,
        private Filesystem $filesystem,
        private ExpressionLanguageServiceInterface $expressionLanguage,
        private Environment $twig,
    )
    {
        $this->expressionLanguage->addPhpFunctions();
        // Project
        $this->project_dir = $this->kernel->getProjectDir();
        // $this->log_dir = $this->kernel->getLogDir();
        // Package
        $this->package_dir = AequationWireBundle::getProjectPath();
    }

    private function getNewFinder(): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(true)
            ->followLinks()
            ;
    }

    public function installConfig(
        string $name
    ): bool
    {
        if($this->hasConfigName($name)) {
            $data = $this->getConfigData($name);
            if(!empty($data)) {
                switch ($name) {
                    case 'manage_entities':
                        return $this->manageEntities($data);
                        break;
                    case 'copy_yaml_files':
                        return $this->copyYamlFiles($data);
                        break;
                }
            }
        }
        return false;
    }

    public function getConfigNames(): array
    {
        $names = static::SOURCES;
        return array_keys($names);
    }

    public function hasConfigName(
        string $name
    ): bool
    {
        $names = static::SOURCES;
        return array_key_exists($name, $names);
    }

    public function findConfigFiles(
        string $name
    ): null|string|array
    {
        if(!isset($this->config_files[$name])) {
            $arrayOfFiles = is_array(static::SOURCES[$name]);
            $files = is_array(static::SOURCES[$name]) ? static::SOURCES[$name] : [static::SOURCES[$name]];
            $this->config_files[$name] = $arrayOfFiles ? [] : null;
            foreach ($files as $subname => $file) {
                // First, search in project
                $filepath = implode(DIRECTORY_SEPARATOR, [$this->project_dir, static::MAIN_PATH, $file]);
                if($this->filesystem->exists($filepath)) {
                    if($arrayOfFiles) {
                        $this->config_files[$name][$subname] = $filepath;
                    } else {
                        $this->config_files[$name] = $filepath;
                    }
                } else {
                    // Second, search in package
                    $filepath = implode(DIRECTORY_SEPARATOR, [$this->package_dir, static::MAIN_PATH, $file]);
                    if($this->filesystem->exists($filepath)) {
                        if($arrayOfFiles) {
                            $this->config_files[$name][$subname] = $filepath;
                        } else {
                            $this->config_files[$name] = $filepath;
                        }
                    }
                }
            }
        }
        return empty($this->config_files[$name])
            ? null
            : $this->config_files[$name];
    }

    private function getYamlFromFile(
        string $file
    ): ?array
    {
        if(file_exists($file) && is_file($file)) {
            return Yaml::parseFile($file);
        }
        return null;
    }

    private function setYamlToFile(
        string $file,
        mixed $data
    ): bool
    {
        if(false === file_put_contents($file, Yaml::dump($data))) {
            return false;
        }
        return file_exists($file);
    }

    public function getConfigData(
        string $name
    ): ?array
    {
        $files = $this->findConfigFiles($name);
        $arrayOfFiles = is_array($files);
        $array = [];
        foreach ((array) $files as $subname => $file) {
            $data = $this->getYamlFromFile($file);
            if(is_array($data)) {
                $new = [
                    'source' => $file,
                    'data' => $this->compileData($data),
                ];
                if($arrayOfFiles) {
                    $array[$subname] = $new;
                } else {
                    return $new;
                }
            }
        }
        return empty($array) ? null : $array;
    }

    public function getAllConfigsData(): array
    {
        $all_data = [];
        foreach ($this->getConfigNames() as $name) {
            $all_data[$name] = $this->getConfigData($name);
        }
        return $all_data;
    }

    private function getDefaultContextValues(): array
    {
        return [
            'package_dir' => $this->package_dir,
            'kernel' => $this->kernel,
        ];
    }

    private function compileData(
        array|string $data,
        ?array $contexts = null
    ): mixed
    {
        if(is_null($contexts)) $contexts = $this->getDefaultContextValues();
        if(is_array($data)) {
            $evaluated = [];
            foreach ($data as $key => $value) {
                $preventCompile = preg_match('/^\\^/', $key);
                $key = preg_replace('/^\\^/', '', $key);
                // if(is_string($key)) $key = $this->compileData($key, $contexts);
                $evaluated[$key] = !$preventCompile && (is_string($value) || is_iterable($value))
                    ? $this->compileData($value, $contexts)
                    : $value;
            }
            return $evaluated;
        }
        try {
            return $this->expressionLanguage->evaluate($data, $contexts);
        } catch (\Throwable $th) {
            return $data;
        }
    }


    /*************************************************************************************************************/
    /** INITIALIZE ACTIONS                                                                                       */
    /*************************************************************************************************************/

    private function manageEntities(
        array $data
    ): bool
    {
        foreach ($data['entities']['data'] as $shortname => $values) {
            $vars = [
                'entity' => new EntityCreator($shortname, $values, $data['options']['data']),
            ];
            $template = '@AequationWire/initializer/models/entity.php.twig';
            if(!$this->twig->getLoader()->exists($template)) {
                throw new Exception(vsprintf('Error %s line %d: template %s not found!', [__METHOD__, __LINE__, $template]));
            }
            $php_code = $this->twig->render($template, $vars);
            $result = file_put_contents(implode(DIRECTORY_SEPARATOR, [$this->project_dir, 'src', 'Entity', $vars['entity']->getFilename()]), $php_code);
            if(!$result) return false;
        }
        return true;
    }

    private function copyYamlFiles(
        array $data
    ): bool
    {
        foreach ($data['data'] as $files_info) {
            $source = $this->getNewFinder()->files()->name($files_info['names'])->in($files_info['from'])->depth(static::SEARCH_DEPTH);
            foreach ($source as $file) {
                $dest_exists = file_exists($files_info['to'].$file->getFilename());
                if(!$dest_exists) $files_info['merge_type'] = 'replace';
                switch ($files_info['merge_type']) {
                    case 'no_merge':
                        // do not replace if exists in project
                    case 'replace':
                        // replace all project data
                        $replace = $files_info['merge_type'] === 'replace';
                        try {
                            $this->filesystem->copy($file->getRealPath(), $files_info['to'].$file->getFilename(), $replace);
                        } catch (\Throwable $th) {
                            throw $th;
                            return false;
                        }
                        break;
                    case 'keep_project':
                        // Keep project data, but add new data in package
                        $source = $this->getYamlFromFile($files_info['to'].$file->getFilename());
                        $destination = $this->getYamlFromFile($file->getRealPath());
                        $source = Iterables::mergeArrays($destination, $source);
                        if(!$this->setYamlToFile($files_info['to'].$file->getFilename(), $source)) {
                            return false;
                        }
                        break;
                    case 'conform_package':
                        // Keep project data, but add new data in package and remove data if not in package
                        $source = $this->getYamlFromFile($file->getRealPath());
                        $destination = $this->getYamlFromFile($files_info['to'].$file->getFilename());
                        $source = Iterables::mergeArrays($destination, $source);
                        if(!$this->setYamlToFile($files_info['to'].$file->getFilename(), $source)) {
                            return false;
                        }
                        break;
                }
            }
        }
        return true;
    }

}