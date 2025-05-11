<?php
namespace Aequation\WireBundle\Component;

use Aequation\WireBundle\Attribute\SerializationMapping;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\interface\RelationMapperInterface;
use Aequation\WireBundle\Entity\interface\BetweenManyInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\ToOneAssociationMapping;
use Doctrine\ORM\Mapping\AssociationMapping;
use Symfony\Component\PropertyAccess\PropertyAccess;
// PHP
use Twig\Markup;
use Closure;
use Exception;
use ReflectionClass;
use Throwable;

class RelationMapper implements RelationMapperInterface
{
    public const SERIALIZATION_MAPPINGS_BY_ATTRIBUTE = true;
    public const TRIGGER_EXCEPTION_ON_ERROR = false;

    public readonly ?ClassMetadata $classMetadata;
    protected array|false $relations;
    protected Opresult $controls;

    public function __construct(
        public readonly string $classname,
        public readonly WireEntityManagerInterface $wireEm,
    ) {
        $this->controls = new Opresult();
        if(!$this->wireEm->entityExists($this->classname, true, false)) {
            $this->addError(vsprintf('Error %s line %d: classname %s is not a valid entity', [__METHOD__, __LINE__, $this->classname]));
        }
        if($this->classMetadata = $this->wireEm->getClassMetadata($this->classname)) {
            $this->computeRelatedDependencies();
        }
    }

    public function isValid(): bool
    {
        return !$this->controls->hasFail();
    }

    private function addError(string $message): void
    {
        if(static::TRIGGER_EXCEPTION_ON_ERROR) {
            throw new Exception($message);
        }
        $this->controls->addError($message);
    }

    public function getControls(): OpresultInterface
    {
        return $this->controls;
    }

    public function getErrorMessages(): array
    {
        return $this->controls->getMessages('danger');
    }

    public function getMessagesAsString(
        ?bool $asHtml = null,
        bool $byTypes = true,
        null|string|array $msgtypes = null
    ): string|Markup
    {
        return $this->controls->getMessagesAsString($asHtml, $byTypes, $msgtypes);
    }

    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }


    /******************************************************************************************/
    /** REPORT                                                                                */
    /******************************************************************************************/

    public function getReport(): array
    {
        $report = [
            'classname' => $this->classname,
            'classMetadata' => $this->classMetadata,
            // 'discriminatorColumn' => $this->classMetadata->getDiscriminatorColumn(),
            'fields' => $this->getFieldMappings(),
            'relations' => $this->getRelations(),
            'valid' => $this->isValid(),
            'errors' => $this->getErrorMessages(),
            'status' => [
                'root' => $this->classMetadata ? $this->classMetadata->isRootEntity() : null,
                'mappedSuperclass' => $this->classMetadata ? $this->classMetadata->isMappedSuperclass : null,
                // 'embeddable' => $this->classMetadata ? $this->classMetadata->isIdGeneratorIdentity() : null,
                // 'entity' => $this->classMetadata ? $this->classMetadata->isIdGeneratorSequence() : null,
            ],
        ];
        return $report;
    }


    /******************************************************************************************/
    /** FIELDS                                                                                */
    /******************************************************************************************/

    public function hasField(string $field): bool
    {
        return $this->classMetadata->hasField($field);
    }

    public function getFieldMapping(string $field): FieldMapping|false
    {
        return $this->hasRelation($field) ? $this->classMetadata->getFieldMapping($field) : false;
    }

    public function getFieldMappings(): array
    {
        $fieldMappings = [];
        if($this->classMetadata) {
            foreach ($this->classMetadata->getFieldNames() as $field) {
                $fieldMappings[$field] = $this->classMetadata->getFieldMapping($field);
            }
        }
        return $fieldMappings;
    }

    public function getRelationValue(object $entity, string $field): null|object|array
    {
        try {
            $value = $this->classMetadata->getFieldValue($entity, $field);
        } catch (Throwable $th) {
            $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
            $value = $accessor->getValue($entity, $field);
        }
        return $value;
    }

    public function setRelationValue(object $entity, string $field, object|array $value): void
    {
        try {
            $this->classMetadata->setFieldValue($entity, $field, $value);
        } catch (Throwable $th) {
            $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
            $accessor->setValue($entity, $field, $value);
        }
    }


    /******************************************************************************************/
    /** RELATIONS                                                                             */
    /******************************************************************************************/

    public function getRelationFieldnames(): array
    {
        return array_keys($this->relations);
    }

    public function hasRelation(string $field): bool
    {
        return isset($this->relations[$field]);
    }

    public function getRelation(string $field): array|false
    {
        return $this->relations[$field] ?? false;
    }

    public function getRelationMapping(string $field): AssociationMapping
    {
        return $this->relations[$field]['mapping'];
    }

    public function isToOneRelation(string $field): bool
    {
        return $this->getRelationMapping($field)->isToOne();
    }

    public function isToManyRelation(string $field): bool
    {
        return $this->getRelationMapping($field)->isToMany();
    }

    public function isRelationCreateOnly(string $field): bool
    {
        $mapp = $this->getRelationMapping($field);
        return $mapp->orphanRemoval && $mapp->isCascadePersist() && $mapp->isToOne();
    }

    public function isAvailableRelation(string $field, object|string $entity): bool
    {
        if($this->hasRelation($field)) {
            try {
                $cmd = $this->wireEm->getClassMetadata($entity);
            } catch (Exception $e) {
                return false;
            }
            $class = $this->relations[$field]['require_metadata'];
            return is_a($entity, $class, true);
        }
        return false;
    }

    public function getRelationTargetClasses(
        string $field,
        bool $onlyInstantiables = true,
    ): array|false
    {
        if($this->hasRelation($field)) {
            if($onlyInstantiables) {
                return $this->relations[$field]['require_instantiable'];
            } else {
                return $this->relations[$field]['require_all'];
            }
        }
        return false;
    }

    public function getRelations(
        ?Closure $filter = null,
    ): array
    {
        if(!isset($this->relations)) return [];
        return is_callable($filter)
            ? array_filter(
                $this->relations, 
                fn(array $mapping, string $field) => $filter($mapping['mapping'], $field, $this->classMetadata),
                ARRAY_FILTER_USE_BOTH
            ) : $this->relations;
    }


    /*************************************************************************************************************************/
    /** INTERNALS                                                                                                            */
    /*************************************************************************************************************************/

    private function computeRelatedDependencies(): void
    {
        // A - Regular associations
        $cmd_mappings = $this->classMetadata->getAssociationMappings();
        // B - Relative associations
        $relative_mappings = $this->getRelativeAssociationMappings();
        /*****************************/
        /** compile all associations */
        /*****************************/
        $this->relations = [];
        // 1 - from relative mappings
        foreach ($relative_mappings as $field => $mapping) {
            if(!isset($cmd_mappings[$mapping['field']])) {
                // assert(isset($cmd_mappings[$mapping['field']]), vsprintf('Error %s line %d: field "%s" should be a Doctrine relation field, in class "%s"!', [__METHOD__, __LINE__, $mapping['field'], $this->classMetadata->name]));
                $this->addError(vsprintf('Error %s line %d: field "%s" should be a Doctrine relation field, in class "%s"!', [__METHOD__, __LINE__, $mapping['field'], $this->classMetadata->name]));
                return;
            }
            $this->relations[$field] = $mapping;
            // $relative_mappings[$field]['field'] --> already set
            $this->relations[$field]['property'] = $field;
            $this->relations[$field]['mapping'] = $cmd_mappings[$mapping['field']];
            // $this->relations[$field]['require'] = $relative_mappings[$field]['require'];
            $this->relations[$field]['require_all'] = $relative_mappings[$field]['require'];
            // $this->relations[$field]['require_instantiable'] = [];
            $this->relations[$field]['require_metadata'] = $cmd_mappings[$mapping['field']]->targetEntity;
            $this->relations[$field]['has_between'] = $this->wireEm::isBetweenEntity($cmd_mappings[$mapping['field']]->targetEntity);
            $this->relations[$field]['described_by'] = 'relative';
            unset($this->relations[$field]['require']);
        }
        // 2 - from Doctrine class metadata
        foreach ($cmd_mappings as $field => $mapping) {
            $this->relations[$field] = array_merge([
                'property' => $mapping->fieldName,
                'field' => $mapping->fieldName,
                'mapping' => $mapping,
                'require_all' => $this->wireEm::isBetweenEntity($mapping->targetEntity) ? [] : [$mapping->targetEntity],
                // 'require_instantiable' => [],
                'require_metadata' => $mapping->targetEntity,
                'has_between' => $this->wireEm::isBetweenEntity($mapping->targetEntity),
                'described_by' => 'metadata',
            ], $this->relations[$field] ?? []);
        }
        // 3 - other informations
        foreach ($cmd_mappings as $field => $mapping) {
            // $this->relations[$field]['isCollectionValuedAssociation'] = $this->classMetadata->isCollectionValuedAssociation($field);
            // $this->relations[$field]['isAssociationWithSingleJoinColumn'] = $this->classMetadata->isAssociationWithSingleJoinColumn($field);
            // $this->relations[$field]['hasAssociation'] = $this->classMetadata->hasAssociation($field);
            $this->relations[$field]['isToOne'] = $this->classMetadata->getAssociationMapping($field)->isToOne();
            $this->relations[$field]['isToMany'] = $this->classMetadata->getAssociationMapping($field)->isToMany();
        }

        // Check classnames
        foreach ($this->relations as $field => $mapping) {
            // Is a between entity
            if($this->wireEm::isBetweenEntity($this->relations[$field]['require_metadata'])) {
                // Change all require_all interfaces into classnames
                $require_all_instantiables = Objects::changeToClassnames($this->relations[$field]['require_all'], $this->wireEm->getEntityNames(false, true, false));
                $between = $this->wireEm->getClassMetadata($this->relations[$field]['require_metadata']);
                if(!$this->relations[$field]['has_between']) {
                    throw new Exception(vsprintf('Error %s line %d: field "%s" should be a Doctrine relation field, in class "%s"!', [__METHOD__, __LINE__, $mapping['field'], $this->classMetadata->name]));
                }
                $this->relations[$field]['has_between'] = $between->name;
                if(!$this->wireEm::isBetweenEntity($between->name)) {
                    $this->addError(vsprintf('Error %s line %d: %s field %s relation to "%s" is not a valid class!', [__METHOD__, __LINE__, $this->classMetadata->name, $field, $between->name]));
                    unset($this->relations[$field]);
                    return;
                }
                // dd($this->relations[$field], $between, $between->getAssociationMappings());
                foreach ($between->getAssociationMappings() as $mapp) {
                    /** @var ToOneAssociationMapping $mapp */
                    if($mapp->inversedBy !== $this->relations[$field]['field']) {
                        $this->relations[$field]['require_metadata'] = $mapp->targetEntity;
                        foreach ($require_all_instantiables as $requ) {
                            if(!is_a($requ, $mapp->targetEntity, true)) {
                                // unset($this->relations[$field]['require_all'][$key]);
                                $this->addError(vsprintf('Error %s line %d: for %s property %s, class "%s" should be instance of %s!%s-> Please check %s data!', [__METHOD__, __LINE__, $this->classMetadata->name, $field, $requ, $mapp->targetEntity, PHP_EOL, BetweenManyInterface::class]));
                                throw new Exception(vsprintf('Error %s line %d: for %s property %s, class "%s" should be instance of %s!%s-> Please check %s data!', [__METHOD__, __LINE__, $this->classMetadata->name, $field, $requ, $mapp->targetEntity, PHP_EOL, BetweenManyInterface::class]));
                            }
                        }
                        if(empty($this->relations[$field]['require_all'])) {
                            $this->relations[$field]['require_all'] = [$mapp->targetEntity];
                        }
                        // $this->relations[$field]['require_all'] = Objects::changeToClassnames($this->relations[$field]['require_all'], $this->wireEm->getEntityNames(false, true, false));
                        break;
                    }
                }
            }
            // Transorm to instantiable entity classes
            $this->relations[$field]['require_instantiable'] = array_values($this->wireEm->resolveFinalEntitiesByNames($this->relations[$field]['require_all'], true));
            foreach ($this->relations[$field]['require_instantiable'] as $requ) {
                if(!is_a($requ, $this->relations[$field]['require_metadata'], true)) {
                    $this->addError(vsprintf('Error %s line %d: for %s property %s, class "%s" should be instance of %s!%sPlease check %s data!', [__METHOD__, __LINE__, $this->classMetadata->name, $field, $requ, $mapp->targetEntity, PHP_EOL, BetweenManyInterface::class]));
                    unset($this->relations[$field]);
                    return;
                }
                $requCr = new ReflectionClass($requ);
                if(!$requCr->isInstantiable()) {
                    $this->addError(vsprintf('Error %s line %d: for %s property %s, class "%s" should be instantiable!%sPlease check %s data!', [__METHOD__, __LINE__, $this->classMetadata->name, $field, $requ, PHP_EOL, BetweenManyInterface::class]));
                    unset($this->relations[$field]);
                    return;
                }
            }
            $this->relations[$field]['classname'] = $this->classMetadata->name;
            $this->relations[$field]['shortname'] = $this->classMetadata->getReflectionClass()->getShortname();
        }
    }

    /**
     * Get relative relation mappings
     * Returns properties of relations not defined in the class metadata
     * 
     * @return array
     */
    private function getRelativeAssociationMappings(): array
    {
        if(!static::SERIALIZATION_MAPPINGS_BY_ATTRIBUTE) {
            // Get serialization mappings by class constant ITEMS_ACCEPT
            $constant = $this->classname.'::ITEMS_ACCEPT';
            $mappings = defined($constant) ? constant($constant) : [];
            $mappings = new SerializationMapping($mappings);
        } else {
            // Get serialization mappings by SerializationMapping attribute
            $mappings = Objects::getClassAttributes($this->classname, SerializationMapping::class);
            // Get first mapping
            /** @var SerializationMapping|false $mapping */
            $mappings = reset($mappings);
        }
        return $mappings instanceof SerializationMapping ? $mappings->getMapping() : [];
    }

}