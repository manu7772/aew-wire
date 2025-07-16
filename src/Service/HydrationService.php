<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\HydradataCollection;
use Aequation\WireBundle\Component\interface\HydradataCollectionInterface;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Component\interface\HydraItemInterface;
use Aequation\WireBundle\Component\interface\HydradataItemsInterface;
use Aequation\WireBundle\Tools\Files;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\HydrationServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Encoders;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
// PHP
use ArrayObject;
use Throwable;

/**
 * Normalizer service
 * @see https://symfony.com/doc/current/serializer.html
 */
#[AsAlias(HydrationServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class HydrationService implements HydrationServiceInterface
{
    use TraitBaseService;
    
    public const CIRCULAR_REFERENCE_LIMIT = 2;

    protected array $dataFiles = [];
    protected array $hydratableData;

    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wireEm,
        public readonly SerializerInterface $serializer,
        public readonly ObjectMapperInterface $objectMapper
    ) {
        // Set the default path for the generator
    }

    public function getSerializer(): SerializerInterface & NormalizerInterface & DenormalizerInterface
    {
        return $this->serializer;
    }

    public function getObjectMapper(): ObjectMapperInterface
    {
        return $this->objectMapper;
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

    public function normalize(mixed $data, ?string $format = null, ?array $context = [], ?bool $convertToArrayList = false): array|string|int|float|bool|ArrayObject|null
    {
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
        $data = $this->getSerializer()->denormalize($data, $classname, $format, $context);
        return $data;
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
        $context[AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE] ??= static::DEEP_POPULATE_MODE;
        $data = $this->getSerializer()->deserialize($data, $type, $format, $context);
        return $data;
    }



    /****************************************************************************************************/
    /** FROM YAML DATA                                                                                  */
    /****************************************************************************************************/

    public function generate(int $index, array $items = [], bool $persist = false, ?string $path = null): OpresultInterface
    {
        $opresult = new Opresult();
        $path ??= static::DEFAULT_DATA_PATH;
        $hydradata = $this->getHydatableData($path);
        if ($hydradata->containsKey($index) && !empty($hydradata->get($index))) {
            /** @var HydradataItemsInterface */
            $hydradataItems = $hydradata->get($index);
            /** @var HydradataItemsInterface */
            $all_data = empty($items) ? $hydradataItems : $hydradataItems->filter(fn ($item) => in_array($item, $items)); // HydradataItemsInterface
            if($all_data->isValid()) {
                if(!$all_data->isEmpty()) {
                    $em = $this->wireEm->getEntityManager();
                    foreach ($all_data as $id => $data) {
                        /** @var HydraItemInterface $data */
                        $entity = $this->generateEntity($data);
                        $opresult->addData($id, ['classname' => $hydradataItems->name, 'entity' => $entity, 'data' => $data]);
                        if($entity) {
                            // $opresult->addSuccess(vsprintf('Hydration data for %s index "%s" has been generated successfully.', [$hydradataItems->name, $index]));
                            if($persist) {
                                $em->persist($entity);
                                $em->flush();
                                // try {
                                // } catch (Throwable $th) {
                                //     $opresult->addError(vsprintf('Failed to <span class="font-bold underline">flush</span> entity for %s index "%s%s":%s', [$hydradataItems->name, $index, $all_data->count() > 1 ? '/'.$id : '', PHP_EOL.'- ERROR: '.$th->getMessage()]));
                                // }
                            }
                        } else {
                            $opresult->addError(vsprintf('Failed to <span class="font-bold underline">generate (%s)</span> hydration data for %s index "%s%s".', [$persist ? 'persist' : 'test', $hydradataItems->name, $index, $all_data->count() > 1 ? '/'.$id : '']));
                        }
                    }
                    // dd($opresult, $opresult->getMessagesTypedForFlash());
                    if($opresult->isSuccess()) {
                        // Persist the entity if required
                        $opresult->addSuccess(vsprintf('%s entities for %s index "%s" has been <span class="font-bold underline">%s</span> successfully.', ['+'.$all_data->count(), $hydradataItems->name, $index, $persist ? 'persisted' : 'tested']));
                    }
                } else {
                    $opresult->addUndone(vsprintf('No data found for %s index "%s".', [$hydradataItems->name, $index]));
                }
            } else {
                $opresult->addError(vsprintf('Hydration data for %s index "%s" is not valid, because:<br>- %s', [$hydradataItems->name, $index, implode('<br>- ', $hydradataItems->getInvalidReasons())]));
            }
        } else {
            $opresult->addError(vsprintf('Hydration data for index "%s" not found in path "%s".', [$index, $path]));
        }
        return $opresult;
    }

    protected function generateEntity(HydraItemInterface $data): ?object
    {
        return $data->getHydratedEntity();
    }

    public function getDataFiles(?string $path = null): array
    {
        if(!is_dir($path)) {
            $path = $this->appWire->getProjectDir($path ?? static::DEFAULT_DATA_PATH);
        }
        if(!is_dir($path) || !is_readable($path)) {
            throw new \RuntimeException(vsprintf('Error %s line %d: the path "%s" is not a %sdirectory.', [__METHOD__, __LINE__, $path, !is_dir($path) ? '' : 'readable ']));
        }
        return $this->dataFiles[$path] ??= Files::listFiles(
            $path,
            fn(SplFileInfo $file) => $file->getExtension() === 'yaml' && $file->isFile()
        );
    }

    public static function parseYamlData(string $yaml): array|false
    {
        $data = [];
        try {
            $yaml = Yaml::parse($yaml);
        } catch (\Exception $e) {
            return false;
        }
        foreach ($yaml as $key => $values) {
            switch ($key) {
                case 'items':
                    foreach ($values ?? [] as $uname => $value) {
                        $data[$key] ??= [];
                        if(Encoders::isUnameFormatValid($uname)) {
                            $value['uname'] ??= $uname;
                        }
                        $data[$key][] = $value;
                    }
                    break;
                default:
                    $data[$key] = $values;
                    break;
            }
        }
        return $data;
    }

    public function getHydatableData(?string $path = null): HydradataCollectionInterface
    {
        $path ??= static::DEFAULT_DATA_PATH;
        return $this->hydratableData[$path] ??= new HydradataCollection($path, $this);
    }


}