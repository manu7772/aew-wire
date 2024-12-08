<?php
namespace Aequation\WireBundle\Service\Initializer;

use Aequation\WireBundle\AequationWireBundle;
use Aequation\WireBundle\Component\EntityCreator;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Service\BaseService;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\ExpressionLanguageServiceInterface;
use Aequation\WireBundle\Service\interface\InitializerInterface;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Tools\Iterables;
use Aequation\WireBundle\Tools\Strings;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
// PHP
use Exception;
use SplFileInfo;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;

#[AsAlias(InitializerInterface::class, public: false)]
#[Autoconfigure(autowire: true, lazy: true)]
class Initializer extends BaseService implements InitializerInterface
{

    private KernelInterface $kernel;
    private array $config_files = [];
    // private string $project_dir;
    // private string $cache_dir;
    // private string $log_dir;
    // private string $package_dir;
    private string $date;
    private bool $ae_wire_installed;

    public function __construct(
        private AppWireServiceInterface $appWire,
        private Filesystem $filesystem,
        private ExpressionLanguageServiceInterface $expressionLanguage,
        private Environment $twig,
    )
    {
        $this->kernel = $this->appWire->kernel;
        $this->date = $this->appWire->getDatetimeTZ()->format('Y-m-d_His');
        $this->expressionLanguage->addPhpFunctions();
        // Project
        // $this->project_dir = $this->kernel->getProjectDir();
        // $this->cache_dir = $this->kernel->getCacheDir();
        // $this->log_dir = $this->kernel->getLogDir();
        // Package
        // $this->package_dir = AequationWireBundle::getPackagePath();
        $this->ae_wire_installed = static::isInstalled();
        // $this->ae_wire_installed = $this->appWire->getParameter('ae_wire_installed', true);
        // if($this->ae_wire_installed) {
        //     // Check in yaml file, if cache is not refreshed
        //     $files = new Files();
        //     $file = $this->appWire->getConfigDir('services.yaml');
        //     $params_yaml = $files->readYamlFile($file);
        //     if(isset($params_yaml['ae_wire_installed'])) {
        //         $this->ae_wire_installed = $params_yaml['ae_wire_installed'];
        //     }
        // }
        dd($this->ae_wire_installed);
    }

    public static function isInstalled(): bool
    {
        $files = new Files();
        $file = AequationWireBundle::getPackagePath('../config/services.yaml');
        // if(!file_exists($file)) throw new Exception(vsprintf('Error %s line %d: file %s not found!', [__METHOD__, __LINE__, $file]));
        $params_yaml = $files->readYamlFile($file);
        return is_array($params_yaml) && isset($params_yaml['ae_wire_installed'])
            ? $params_yaml['ae_wire_installed']
            : false;
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
    ): OpresultInterface
    {
        $opresult = new Opresult();
        if($this->hasConfigName($name)) {
            foreach ($this->getConfigData($name) as $data) {
                if(!empty($data)) {
                    switch ($name) {
                        case 'manage_entities':
                            if($this->manageEntities($data)) {
                                $opresult->addSuccess(vsprintf('Initialization "%s" done successfully', [$name]));
                            } else {
                                $opresult->addDanger(vsprintf('Initialization "%s" failed!', [$name]));
                            }
                            break;
                        case 'insert_yaml_configs':
                            if($this->insertYamlConfigs($data)) {
                                $opresult->addSuccess(vsprintf('Initialization "%s" done successfully', [$name]));
                            } else {
                                $opresult->addDanger(vsprintf('Initialization "%s" failed!', [$name]));
                            }
                            break;
                        case 'copy_config_files':
                            if($this->copyConfigFiles($data)) {
                                $opresult->addSuccess(vsprintf('Initialization "%s" done successfully', [$name]));
                            } else {
                                $opresult->addDanger(vsprintf('Initialization "%s" failed!', [$name]));
                            }
                            break;
                        case 'textfiles_actions':
                            if($this->textfilesActions($data)) {
                                $opresult->addSuccess(vsprintf('Initialization "%s" done successfully', [$name]));
                            } else {
                                $opresult->addDanger(vsprintf('Initialization "%s" failed!', [$name]));
                            }
                            break;
                        default:
                            $opresult->addWarning(vsprintf('Initialization "%s" not supported yet!', [$name]));
                            break;
                    }
                }
            }
        } else {
            $opresult->addWarning(vsprintf('Initialization "%s" not found!', [$name]));
        }
        return $opresult;
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
    ): array
    {
        if(!isset($this->config_files[$name])) {    
            $files = (array)static::SOURCES[$name];
            $this->config_files[$name] = [];
            foreach ($files as $subname => $file) {
                $search_dirs = [
                    // First, search in project
                    $this->appWire->getProjectDir(static::MAIN_PATH),
                    // Second, search in package
                    AequationWireBundle::getPackagePath(static::MAIN_PATH),
                ];
                $filelocator = new FileLocator($search_dirs);
                $found = (array)$filelocator->locate($file);
                if(count($found)) {
                    $this->config_files[$name][$subname] = reset($found);
                }
            }
        }
        // dd($this->config_files);
        return $this->config_files[$name];
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
        $this->filesystem->dumpFile($file, Yaml::dump($data, 12));
        return file_exists($file);
    }

    public function getConfigData(
        string $name
    ): ?array
    {
        $files = $this->findConfigFiles($name);
        if(empty($files)) return null;
        $array = [];
        foreach ((array)$files as $subname => $file) {
            $data = $this->getYamlFromFile($file);
            if(is_array($data)) {
                $new = [
                    'source' => $file,
                    'data' => $this->compileData($data),
                ];
                $array[$subname] = $new;
            }
        }
        return empty($array) ? null : $array;
    }

    // public function getAllConfigsData(): array
    // {
    //     $all_data = [];
    //     foreach ($this->getConfigNames() as $name) {
    //         $all_data[$name] = $this->getConfigData($name);
    //     }
    //     return $all_data;
    // }

    private function getDefaultContextValues(): array
    {
        return [
            'package_dir' => AequationWireBundle::getPackagePath(),
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

    private function saveFile(
        string $file
    ): bool
    {
        $file = new SplFileInfo($file);
        $path = Strings::getAfter($file->getPath(), $this->appWire->getProjectDir());
        $dest = dirname($this->appWire->getCacheDir('old_configs'.DIRECTORY_SEPARATOR.$this->date.$path));
        // $copy_name = $dest.DIRECTORY_SEPARATOR.Strings::getBeforeLast($file->getFilename(), '.').'_'.$this->date.'.'.$file->getExtension();
        $copy_name = $dest.DIRECTORY_SEPARATOR.$file->getFilename();
        // dd($file, $copy_name, file_exists($copy_name));
        $this->filesystem->copy($file->getRealPath(), $copy_name, true);
        return file_exists($copy_name);
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
            $result = $this->filesystem->dumpFile(implode(DIRECTORY_SEPARATOR, [$this->appWire->getProjectDir(), 'src', 'Entity', $vars['entity']->getFilename()]), $php_code);
            if(!$result) return false;
        }
        return true;
    }

    private function insertYamlConfigs(
        array $data
    ): bool
    {
        foreach ($data['data'] as $config_file) {
            if(!($config_file['enabled'] ?? true)) continue;
            $file_exists = file_exists($config_file['file']);
            if(!$file_exists) {
                // File not found
                $create_file = $config_file['create_file'] ?? true;
                if(!$create_file) continue;
                $file_data = [];
            } else {
                $this->saveFile($config_file['file']);
                $file_data = $this->getYamlFromFile($config_file['file']);
            }
            // dump($config_file, $file_data);
            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
            foreach ($config_file['insertions'] as $insertion) {
                $location = preg_split('/(\s*\/+\s*)/', $insertion['location']);
                $insertion['location'] = '['.implode('][', $location).']';
                // dump($insertion);
                $origin = $propertyAccessor->getValue($file_data, $insertion['location']);
                switch (true) {
                    // case empty($insertion['data']):
                    //     break;
                    case is_iterable($insertion['data']) && array_is_list($insertion['data']):
                        // list array
                        if(is_null($origin) || empty($origin)) $origin = [];
                        if(!is_iterable($origin)) throw new Exception(vsprintf('Error %s line %d: origin data (of type %s) of path "%s" is not compatible with data to insert (type %s)', [__METHOD__, __LINE__, gettype($insertion['data']), $insertion['location'], gettype($origin)]));
                        if(!array_is_list($origin) && !empty($origin)) throw new Exception(vsprintf('Error %s line %d: origin data (of type NOT LIST %s) of path "%s" is not compatible with data to insert (type LIST %s)', [__METHOD__, __LINE__, gettype($insertion['data']), $insertion['location'], gettype($origin)]));
                        // dump(vsprintf('Info %s line %d: origin data (of type %s) of path "%s" is applicable with data to insert (type %s)', [__METHOD__, __LINE__, gettype($insertion['data']), $insertion['location'], gettype($origin)]));
                        $replace = $insertion['replace'] ?? false;
                        if(empty($origin) || $replace) {
                            $origin = array_values($origin);
                            $insertion['data'] = array_values($insertion['data']);
                            $propertyAccessor->setValue($file_data, $insertion['location'], array_values(array_unique(array_merge($origin, $insertion['data']))));
                        }
                        break;
                    case is_iterable($insertion['data']):
                        // string indexed array
                        if(is_null($origin) || empty($origin)) $origin = [];
                        if(!is_iterable($origin)) throw new Exception(vsprintf('Error %s line %d: origin data (of type %s) of path "%s" is not compatible with data to insert (type %s)', [__METHOD__, __LINE__, gettype($insertion['data']), $insertion['location'], gettype($origin)]));
                        if(array_is_list($origin) && !empty($origin)) throw new Exception(vsprintf('Error %s line %d: origin data (of type LIST %s) of path "%s" is not compatible with data to insert (type NOT LIST %s)', [__METHOD__, __LINE__, gettype($insertion['data']), $insertion['location'], gettype($origin)]));
                        // dump(vsprintf('Info %s line %d: origin data (of type %s) of path "%s" is applicable with data to insert (type %s)', [__METHOD__, __LINE__, gettype($insertion['data']), $insertion['location'], gettype($origin)]));
                        $replace = $insertion['replace'] ?? false;
                        if(empty($origin) || $replace) {
                            $type = $insertion['type'] ?? 'add';
                            switch ($type) {
                                case 'add':
                                    // Add only inexistants
                                    if(isset($insertion['before_index']) && array_key_exists($insertion['before_index'], $origin)) {
                                        $merged = [];
                                        foreach ($origin as $key => $value) {
                                            if($key === $insertion['before_index']) {
                                                foreach ($insertion['data'] as $key2 => $value2) {
                                                    $merged[$key2] = $value2;
                                                }
                                            }
                                            $merged[$key] = $value;
                                        }
                                        $propertyAccessor->setValue($file_data, $insertion['location'], $merged);
                                    } else if(isset($insertion['after_index']) && array_key_exists($insertion['after_index'], $origin)) {
                                        $merged = [];
                                        foreach ($origin as $key => $value) {
                                            $merged[$key] = $value;
                                            if($key === $insertion['after_index']) {
                                                foreach ($insertion['data'] as $key2 => $value2) {
                                                    if(!array_key_exists($key2, $origin)) $merged[$key2] = $value2;
                                                }
                                            }
                                        }
                                        $propertyAccessor->setValue($file_data, $insertion['location'], $merged);
                                    } else {
                                        $propertyAccessor->setValue($file_data, $insertion['location'], Iterables::mergeArrays($insertion['data'], $origin));
                                    }
                                case 'merge':
                                    // Add and replace if existant
                                    if(isset($insertion['before_index']) && array_key_exists($insertion['before_index'], $origin)) {
                                        $merged = [];
                                        foreach ($origin as $key => $value) {
                                            if($key === $insertion['before_index']) {
                                                foreach ($insertion['data'] as $key2 => $value2) {
                                                    $merged[$key2] = $value2;
                                                }
                                            }
                                            $merged[$key] = $value;
                                        }
                                        $propertyAccessor->setValue($file_data, $insertion['location'], $merged);
                                    } else if(isset($insertion['after_index']) && array_key_exists($insertion['after_index'], $origin)) {
                                        $merged = [];
                                        foreach ($origin as $key => $value) {
                                            $merged[$key] = $value;
                                            if($key === $insertion['after_index']) {
                                                foreach ($insertion['data'] as $key2 => $value2) $merged[$key2] = $value2;
                                            }
                                        }
                                        $propertyAccessor->setValue($file_data, $insertion['location'], $merged);
                                    } else {
                                        $propertyAccessor->setValue($file_data, $insertion['location'], Iterables::mergeArrays($origin, $insertion['data']));
                                    }
                                    break;
                                default:
                                    // replace
                                    $propertyAccessor->setValue($file_data, $insertion['location'], $insertion['data']);
                                    break;
                            }
                        }
                        break;
                    default:
                        // dump('Data is '.gettype($insertion['data']));
                        $replace = $insertion['replace'] ?? false;
                        if(empty($origin) || $replace) {
                            $propertyAccessor->setValue($file_data, $insertion['location'], $insertion['data']);
                        }
                        break;
                }
            }
            dump($file_data);
            if(!$this->setYamlToFile($config_file['file'], $file_data));
        }
        return true;
    }

    private function textfilesActions(
        array $data
    ): bool
    {
        foreach ($data['data'] as $file) {
            dump($file);
            if(!$this->filesystem->exists($file['file'])) {
                throw new Exception(vsprintf('Error %s line %d: file %s not found!', [__METHOD__, __LINE__, $file['file']]));
                return false;
            }
            $content = $this->filesystem->readFile($file['file']);
            $content = explode(PHP_EOL, $content);
            dump($content);
            foreach ($file['text_lines'] as $addtext) {
                $replace = $addtext['replace'] ?? true;
                $found = -1;
                $pos = -1;
                $where = $addtext['where'] ?? null;
                foreach ($content as $key => $line) {
                    if(preg_match($addtext['search'], $line)) {
                        $found = $key;
                        break;
                    }
                    if(!empty($where) && $pos < 0 && preg_match($where, $line)) {
                        $pos = $key;
                    }
                }
                if($found < 0 || $replace) {
                    if($found >= 0) {
                        $content[$found] = $addtext['text'];
                    } else {
                        if($pos < 0) {
                            // At the end
                            $content[] = $addtext['text'];
                        } else {
                            array_splice($content, $pos, 0, $addtext['text']);
                        }
                    }
                }
            }
            $this->saveFile($file['file']);
            $this->filesystem->dumpFile($file['file'], implode(PHP_EOL, $content), false);
        }
        return true;
    }

    private function copyConfigFiles(
        array $data
    ): bool
    {
        foreach ($data['data'] as $files_info) {
            $source = $this->getNewFinder()->files()->name($files_info['names'])->in($files_info['from'])->depth(static::SEARCH_DEPTH);
            foreach ($source as $file) {
                $target_file = $files_info['to'].$file->getFilename();
                $dest_exists = file_exists($target_file);
                if(!$dest_exists) $files_info['merge_type'] = 'replace';
                if($dest_exists) $this->saveFile($target_file);
                $file_info = new SplFileInfo($target_file);
                $extension = $file_info->getExtension();
                if(!preg_match("/(ya?ml)$/", $target_file)) {
                    if($files_info['merge_type'] === 'keep_project') {
                        $files_info['merge_type'] = 'no_merge';
                    }
                    if($files_info['merge_type'] === 'conform_package') {
                        $files_info['merge_type'] = 'replace';
                    }
                }
                switch ($files_info['merge_type']) {
                    case 'no_merge':
                        // do not replace if exists in project
                    case 'replace':
                        // replace all project data
                        $replace = $files_info['merge_type'] === 'replace';
                        try {
                            $this->filesystem->copy($file->getRealPath(), $target_file, $replace);
                        } catch (\Throwable $th) {
                            dd($th->getMessage());
                            return false;
                        }
                        break;
                    case 'keep_project':
                        // Keep project data, but add new data in package
                        $source = $this->getYamlFromFile($target_file);
                        $destination = $this->getYamlFromFile($file->getRealPath());
                        $source = Iterables::mergeArrays($destination, $source);
                        if(!$this->setYamlToFile($target_file, $source)) {
                            return false;
                        }
                        break;
                    case 'conform_package':
                        // Keep project data, but add new data in package and remove data if not in package
                        $source = $this->getYamlFromFile($file->getRealPath());
                        $destination = $this->getYamlFromFile($target_file);
                        $source = Iterables::mergeArrays($destination, $source);
                        if(!$this->setYamlToFile($target_file, $source)) {
                            return false;
                        }
                        break;
                }
            }
        }
        return true;
    }

}