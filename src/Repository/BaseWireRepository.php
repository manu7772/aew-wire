<?php
namespace Aequation\WireBundle\Repository;

use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\TraitHasOrderedInterface;
use Aequation\WireBundle\Entity\interface\TraitSlugInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
// PHP
use Exception;

abstract class BaseWireRepository extends ServiceEntityRepository implements BaseWireRepositoryInterface
{

    public function __construct(
        ManagerRegistry $registry,
        public readonly AppWireServiceInterface $appWire,
    )
    {
        parent::__construct(registry: $registry, entityClass: static::ENTITY_CLASS);
        if($this->appWire->isDev()) {
            if($this->getEntityName() !== static::ENTITY_CLASS) throw new Exception(vsprintf('Error %s line %d: in %s, entity classes %s and %s do not match!', [__METHOD__, __LINE__, __CLASS__, $this->getEntityName(), static::ENTITY_CLASS]));
        }
    }


    /*************************************************************************************************/
    /** BASE TOOLS                                                                                   */
    /*************************************************************************************************/

    public function hasField(string $name): bool
    {
        $cmd = $this->getClassMetadata();
        return array_key_exists($name, $cmd->fieldMappings);
    }

    public function hasRelation(string $name): bool
    {
        $cmd = $this->getClassMetadata();
        return array_key_exists($name, $cmd->associationMappings);
    }

    public static function alias(): string
    {
        return static::getDefaultAlias();
    }

    public static function getDefaultAlias(): string
    {
        return static::NAME;
    }

    protected static function getAlias(QueryBuilder $qb): string
    {
        $from = $qb->getDQLPart('from');
        /** @var From */
        $from = reset($from);
        $aliases = $qb->getRootAliases();
        if($from instanceof From) return $from->getAlias();
        return count($aliases) ? reset($aliases) : static::getDefaultAlias();
    }

    protected static function getFrom(QueryBuilder $qb): ?string
    {
        $from = $qb->getDQLPart('from');
        /** @var From */
        $from = reset($from);
        return $from instanceof From ? $from->getFrom() : null;
    }


    /*************************************************************************************************/
    /** FORM TYPE UTILITIES                                                                          */
    /*************************************************************************************************/

    public function getChoicesForType(
        string $field,
        ?array $search = [],
        bool $apply_context = true,
    ): array
    {
        $alias = $this->getQB_findBy($search, $apply_context, $qb);
        $qb->select($alias.'.id');
        if(!in_array($field, ['id'])) {
            $qb->addSelect($alias.'.'.$field);
        }
        $results = $qb->getQuery()->getArrayResult();
        $choices = [];
        foreach ($results as $result) {
            $choices[$result[$field]] = $result['id'];
        }
        return $choices;
    }

    public function getCollectionChoices(
        string|TraitHasOrderedInterface $classOrEntity,
        string $property,
        array $exclude_ids = [],
    ): array
    {
        $qb = $this->createQueryBuilder(static::alias());
        if($this->appWire->isDev()) {
            // Check
            $this->checkFromAndSearchClasses($qb, $classOrEntity::ITEMS_ACCEPT[$property], true);
        }
        static::getQB_orderedChoicesList($qb, $classOrEntity, $property, $exclude_ids);
        return $qb->getQuery()->getResult();
    }

    public static function getQB_orderedChoicesList(
        QueryBuilder $qb,
        string|TraitHasOrderedInterface $classOrEntity,
        string $property,
        array $exclude_ids = [],
    ): QueryBuilder
    {
        // if(is_string($classOrEntity) && !is_a($classOrEntity, TraitHasOrderedInterface::class)) {
        //     throw new Exception(vsprintf('Error %s line %d: entity %s is not interface of %s', [__METHOD__, __LINE__, $classOrEntity, TraitHasOrderedInterface::class]));
        // }
        $classes = $classOrEntity::ITEMS_ACCEPT[$property];
        // $qb ??= $this->createQueryBuilder(static::alias());
        $alias = static::getAlias($qb);

        $whr = "($alias.classname IN (:classnames) OR $alias.shortname IN (:shortnames))";
        $parameters = new ArrayCollection([
            new Parameter('classnames', $classes),
            new Parameter('shortnames', $classes),
        ]);
        if(!empty($exclude_ids)) {
            $whr .= " AND $alias.id NOT IN (:ids)";
            $parameters->add(new Parameter('ids', $exclude_ids));
        }
        $qb->where($whr);
        $qb->setParameters($parameters);

        // $qb->andWhere($alias.'.classname IN (:classname)')
        //     ->setParameter('classname', $classes);
        // $qb->orWhere($alias.'.shortname IN (:shortname)')
        //     ->setParameter('shortname', $classes);
        // if(!empty($exclude_ids)) {
        //     $qb->andWhere($alias.'.id NOT IN (:ids)')
        //     ->setParameter('ids', $exclude_ids);
        // }
        static::__filter_Enabled($qb);
        // dd($qb->getQuery()->getResult());
        return $qb;
    }

    public static function checkFromAndSearchClasses(
        QueryBuilder $qb,
        array|string $classes,
        bool $throwsException = false,
    ): bool
    {
        $from = static::getFrom($qb);
        // dump('Testing From: '.static::getFrom($qb).' (Alias: '.static::getAlias($qb).') => '.json_encode($classes));
        foreach ((array)$classes as $class) {
            if(!is_a($class, $from, true)) {
                if($throwsException) {
                    throw new Exception(vsprintf('Error %s line %d: entities of class %s can not be found in %s class', [__METHOD__, __LINE__, $class, $from]));
                }
                return false;
            }
        }
        return true;
    }


    /*************************************************************************************************/
    /** TRY FIND BY HIDRATATION DATA                                                                 */
    /*************************************************************************************************/

    public function tryFindExistingEntity(
        string|array $dataOrUname,
        ?array $uniqueFields = null,
    ): ?WireEntityInterface
    {
        if(is_array($dataOrUname)) {
            // Got array of data
            if(empty($uniqueFields)) $uniqueFields = WireEntityManagerInterface::getConstraintUniqueFields($this->getClassName(), false);
            if(count($uniqueFields)) {
                if(!is_array(reset($uniqueFields))) $uniqueFields = [$uniqueFields];
                foreach ($uniqueFields as $fields) {
                    $search = [];
                    foreach ($fields as $field) {
                        if(isset($dataOrUname[$field])) $search[$field] = $dataOrUname[$field];
                    }
                    if(!empty($search)) {
                        $find = $this->findOneBy($search);
                        if($find) return $find;
                    }
                }
            }
        }
        return is_string($dataOrUname)
            ? $this->findEntityByEuidOrUname($dataOrUname) // Got Uname
            : null;
    }

    public function findEntityByEuidOrUname(
        string $uname
    ): ?WireEntityInterface
    {
        $qb = $this->createQueryBuilder(static::alias())
            ->where(static::alias().'.euid = :uname')
            ->setParameter('uname', $uname);
        if($this->hasRelation('uname')) {
            $qb->leftJoin(static::alias().'.uname', 'uname')
                ->orWhere('uname.uname = :uname');
        }
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findOneByEuid(string $euid): ?WireEntityInterface
    {
        return $this->findOneByEuid($euid);
    }


    /*************************************************************************************************/
    /** SLUGGABLE ENTITIES                                                                           */
    /*************************************************************************************************/

    /**
     * Find all slugs of a class
     * @param integer|null $exclude_id
     * @return array
     */
    public function findAllSlugs(
        ?int $exclude_id = null
    ): array
    {
        $slugs = [];
        if(is_a($this->getEntityName(), TraitSlugInterface::class, true)) {
            $qb = $this->createQueryBuilder(static::alias())->select(static::alias().'.id, '.static::alias().'.slug');
            if(!empty($exclude_id)) {
                $qb->where(static::alias().'.id != :exclude')->setParameter('exclude', $exclude_id);
            }
            $results = $qb->getQuery()->getArrayResult();
            foreach ($results as $result) {
                $slugs[$result['id']] = $result['slug'];
            }
        }
        return $slugs;
    }

    /*************************************************************************************************/
    /** add to QueryBuilders                                                                         */
    /*************************************************************************************************/

    protected function __apply_context(
        QueryBuilder $qb,
    ): void
    {
        switch (true) {
            case $this->appWire->isPublic():
                $this->__filter_Enabled($qb);
                $this->__filter_User($qb);
                break;
        }
    }

    protected function __filter_Enabled(QueryBuilder $qb): void
    {   
        if(is_a(static::getFrom($qb), TraitEnabledInterface::class, true)) {
            $alias = static::getAlias($qb);
            $qb->andWhere($alias.'.enabled = :enabled')
                ->setParameter('enabled', true)
                ->andWhere($alias.'.softdeleted = :softd')
                ->setParameter('softd', false)
                ;
        }
    }

    protected function __filter_User(QueryBuilder $qb): void
    {
        if(is_a($this->getEntityName(), WireUserInterface::class, true)) {
            $alias = static::getAlias($qb);
            $qb->andWhere($alias.'.expiresAt IS NULL OR '.$alias.'.expiresAt > :now')
                ->setParameter('now', $this->appWire->getCurrentDatetime())
                ;
        }
    }

    protected function __filter_by(
        QueryBuilder $qb,
        array $search,
    ): void
    {
        $alias = static::getAlias($qb);
        foreach ($search as $field => $value) {
            $fields = explode('.', $field);
            $name = reset($fields);
            $neg = '';
            if(preg_match('/^!/', $name)) {
                $neg = is_array($value) ? 'NOT ' : '!';
                $name = preg_replace('/^!*/', '', $name);
            }
            switch (true) {
                case array_key_exists($name, $this->getClassMetadata()->fieldMappings):
                    # field
                    $comp = is_array($value) ? ' '.$neg.'IN (:'.$name.')' : ' '.$neg.'= :'.$name;
                    $qb->andWhere($alias.'.'.$name.$comp)
                        ->setParameter($name, $value)
                        ;
                    break;
                case array_key_exists($name, $this->getClassMetadata()->associationMappings):
                    # association
                    // dd($search, $alias.'.'.$name);
                    $v = reset($value);
                    if($v) {
                        $v = $v->getId();
                        // $comp = is_array($value) ? ' '.$neg.'IN (:'.$name.')' : ' '.$neg.'= :'.$name;
                        $qb->leftJoin($alias.'.'.$name, $name)
                            // ->andWhere($name.$comp)
                            ->andWhere($qb->expr()->in($name, [$v]))
                            // ->setParameter($name, $value)
                            ;
                    }
                    break;
                default:
                    throw new Exception(vsprintf('Field or association named "%s" (searching with "%s" with value "%s") not found!', [reset($fields), $field, (string)$value]));
                    break;
            }
        }
    }

    /*************************************************************************************************/
    /** get QueryBuilders                                                                            */
    /*************************************************************************************************/

    public function getQB_findBy(
        ?array $search,
        bool $apply_context = true,
        ?QueryBuilder &$qb
    ): string
    {
        $alias = static::getAlias($qb);
        $qb ??= $this->createQueryBuilder($alias);
        if($apply_context) {
            $this->__apply_context($qb);
        }
        if(!empty($search)) {
            $this->__filter_by($qb, $search);
        }
        return $alias;
    }

}