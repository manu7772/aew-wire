<?php
namespace Aequation\WireBundle\Service;

// Aequation
use Exception;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityRepository;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Tools\Encoders;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Aequation\WireBundle\Tools\HttpRequest;
use Symfony\Component\Validator\Constraint;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Aequation\WireBundle\Component\EntityContainer;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Aequation\WireBundle\Entity\interface\WirePdfInterface;
use Aequation\WireBundle\Component\WireClassMetadataManager;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Aequation\WireBundle\Entity\interface\WireImageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Aequation\WireBundle\Entity\interface\BaseEntityInterface;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Entity\interface\TraitEnabledInterface;
use Aequation\WireBundle\Entity\interface\WireLanguageInterface;
// Symfony
use Aequation\WireBundle\Service\interface\CacheServiceInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Aequation\WireBundle\Entity\interface\TraitDatetimedInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Entity\interface\TraitWebpageableInterface;
use Aequation\WireBundle\Service\interface\SurveyRecursionInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Component\interface\EntityContainerInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireEntityServiceInterface;
use Aequation\WireBundle\Service\interface\WireLanguageServiceInterface;
// PHP
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Component\interface\WireClassMetadataManagerInterface;
use Aequation\WireBundle\Tools\Objects;

/**
 * Class WireEntityManager
 * @package Aequation\WireBundle\Service
 */
#[AsAlias(WireEntityManagerInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class WireEntityManager implements WireEntityManagerInterface
{
    use TraitBaseService;

    private array $__src = [];
    // Criteria
    public const CRITERIA_ENABLED = ['enabled' => true];
    public const CRITERIA_DISABLED = ['enabled' => false];

    protected readonly UnitOfWork $uow;
    public int $hydrate_mode = 0;
    protected array $postFlushInfos = [];
    protected array $relatedDependencies = [];
    protected readonly WireClassMetadataManagerInterface $entitiesMetadata;

    /**
     * constructor.
     * 
     * @param EntityManagerInterface $em
     * @param AppWireServiceInterface $appWire
     * @param CacheServiceInterface $cacheService
     * @param UploaderHelper $vichHelper
     * @param CacheManager $liipCache
     * @param LoggerInterface $logger
     * @param SurveyRecursionInterface $surveyRecursion
     */
    public function __construct(
        public readonly EntityManagerInterface $em,
        public readonly AppWireServiceInterface $appWire,
        public readonly CacheServiceInterface $cacheService,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
        public readonly ValidatorInterface $validator,
        public readonly LoggerInterface $logger,
        public readonly SurveyRecursionInterface $surveyRecursion,
    ) {
    }


    public function getNormaliserService(): NormalizerServiceInterface
    {
        return $this->appWire->get(NormalizerServiceInterface::class);
    }


    /****************************************************************************************************/
    /** HYDRATE MODE                                                                                    */
    /****************************************************************************************************/

    public function isHydrateMode(): bool
    {
        return $this->hydrate_mode > 0 || HttpRequest::isCli();
    }
    
    public function incHydrateMode(): bool
    {
        $this->hydrate_mode++;
        return $this->isHydrateMode();
    }

    public function decHydrateMode(): bool
    {
        $this->hydrate_mode--;
        return $this->isHydrateMode();
    }

    public function resetHydrateMode(): bool
    {
        $this->hydrate_mode = 0;
        return $this->isHydrateMode();
    }

    public function isDev(): bool
    {
        return $this->appWire->isDev();
    }

    public function isProd(): bool
    {
        return $this->appWire->isProd();
    }


    /****************************************************************************************************/
    /** SERVICES                                                                                        */
    /****************************************************************************************************/

    /**
     * get AppWireService
     *
     * @return AppWireServiceInterface
     */
    public function getAppWireService(): AppWireServiceInterface
    {
        return $this->appWire;
    }

    /**
     * get entity service
     *
     * @param string|BaseEntityInterface $entity
     * @return ?WireEntityServiceInterface
     */
    public function getEntityService(string|BaseEntityInterface $entity): ?WireEntityServiceInterface
    {
        return $this->getEntitiesMetadata()->getService($entity);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getEm(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->uow ??= $this->em->getUnitOfWork();
    }

    public function getUow(): UnitOfWork
    {
        return $this->getUnitOfWork();
    }


    /****************************************************************************************************/
    /** GENERATION                                                                                      */
    /****************************************************************************************************/

    /**
     * create entity
     * 
     * @param string $classname
     * @param string|null $uname
     * @return object
     */
    public function createEntity(string $classname, array $data = [], array $context = []): object
    {
        $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        $wCmd = $this->getEntitiesMetadata()->findOneInstantiable([$classname]);
        if(!$this->appWire->isGranted('new', $wCmd->name)) {
            throw new Exception(vsprintf('Error %s line %d: you are not allowed to create %s!', [__METHOD__, __LINE__, $classname]));
        }
        if($service = $this->getEntityService($classname)) {
            return $service->createEntity($data, $context);
        }
        return $wCmd->newInstance($data);
    }

    /**
     * create model
     * 
     * @return BaseEntityInterface
     */
    public function createModel(string $classname, array $data = [], array $context = []): BaseEntityInterface
    {
        $this->surveyRecursion->survey(__METHOD__.'::'.$classname);
        if($service = $this->getEntityService($classname)) {
            return $service->createModel($data, $context);
        }
        $wCmd = $this->getEntitiesMetadata()->findOneInstantiable([$classname]);
        return $wCmd->newModel($data);
    }

    /**
     * create clone
     * 
     * @return BaseEntityInterface|null
     */
    public function createClone(BaseEntityInterface $entity, array $changes = [], array $context = []): BaseEntityInterface|false
    {
        if(!$this->appWire->isGranted('new', $entity)) {
            throw new Exception(vsprintf('Error %s line %d: you are not allowed to clone %s!', [__METHOD__, __LINE__, $entity]));
        }
        throw new Exception('Not implemented yet!');
    }


    /****************************************************************************************************/
    /** VALIDATION                                                                                      */
    /****************************************************************************************************/

    public function validateEntity(object $entity, string|GroupSequence|array|null $addGroups = null, Constraint|array|null $constraints = null, bool $throws = false): ConstraintViolationListInterface
    {
        $errors = $this->getEntitiesMetadata()->validateEntity($entity, $addGroups, $constraints);
        if($throws && $errors->count() > 0) {
            throw new Exception(vsprintf('Error %s line %d:%s%s', [__METHOD__, __LINE__, PHP_EOL.'On '.Objects::toDebugString($entity), PHP_EOL.'- '.implode(PHP_EOL.'- ', Objects::violationListToArray($errors))]));
        }
        return $errors;
    }

    /****************************************************************************************************/
    /** REPOSITORY / QUERYS WITH IDENTITY (EUID, UNAME, ...)                                            */
    /****************************************************************************************************/

    public function getRepository(string|object $objectOrClass): ?EntityRepository
    {
        return $this->getEntitiesMetadata()->getRepository($objectOrClass);
    }

    // /**
    //  * get repository
    //  * 
    //  * @param string $classname
    //  * @param string|null $field
    //  * @return BaseWireRepositoryInterface
    //  */
    // public function getRepository(
    //     string $classname,
    //     ?string $field = null // if field, find repository where is declared this $field
    // ): BaseWireRepositoryInterface
    // {
    //     $cmd = $this->getClassMetadata($classname);
    //     $classname = $cmd->name;
    //     if($field) {
    //         // Find classname where field is declared
    //         if(array_key_exists($field, $cmd->fieldMappings)) {
    //             $test_classname = $cmd->fieldMappings[$field]->declared ?? $classname;
    //         } else if(array_key_exists($field, $cmd->associationMappings)) {
    //             $test_classname = $cmd->associationMappings[$field]->declared ?? $classname;
    //         } else {
    //             // Not found, tant pis...
    //         }
    //         if(isset($test_classname)) {
    //             $test_cmd = $this->getClassMetadata($test_classname);
    //             if(!$test_cmd->isMappedSuperclass) $classname = $test_classname;
    //         }
    //     }
    //     /** @var BaseWireRepositoryInterface */
    //     $repo = $this->em->getRepository($classname);
    //     // if(!empty($field)) dump($classname, $field, get_class($repo));
    //     if($this->isDev() && !($repo instanceof BaseWireRepositoryInterface)) {
    //         dd($this->__toString(), $classname, $cmd, $cmd->name, $repo);
    //     }
    //     return $repo;
    // }

    /**
     * find entity by id
     * 
     * @param int|string $id
     * @return BaseEntityInterface|null
     */
    public function findById(string $classname, string $id): ?BaseEntityInterface
    {
        $repo = $this->em->getRepository($classname);
        return $repo->find($id);
    }

    public function findByEuid(string $euid): ?BaseEntityInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($euid))) {
            return $entity;
        }
        $class = Encoders::getClassOfEuid($euid);
        $repo = $this->em->getRepository($class);
        $entity = $repo->findOneBy(['euid' => $euid]);
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function euidExists(string $euid, bool $getData = false): bool|null|array
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($euid))) {
            return true;
        }
        $class = Encoders::getClassOfEuid($euid);
        $repo = $this->em->getRepository($class);
        $entity = $repo
            ->createQueryBuilder('u')
            ->select('u.id, u.classname','u.euid')
            ->where('u.euid = :euid')
            ->setParameter('euid', $euid)
            ->getQuery()
            ->getScalarResult()
            ;
        if(empty($entity)) $entity = null;
        return $getData ? $entity : !empty($entity);
    }

    public function findByUname(string $uname): ?BaseEntityInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($uname))) {
            return $entity;
        }
        $unameOjb = $this->getRepository(Uname::class)->find($uname);
        $entity = $unameOjb instanceof UnameInterface
            ? $this->findByEuid($unameOjb->getEntityEuid())
            : null;
        return $entity instanceof BaseEntityInterface ? $entity : null;
    }

    public function findUnameByUname(string $uname): ?UnameInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findUnameCreated($uname))) {
            return $entity;
        }
        return $this->findById(Uname::class, $uname);
    }

    public function getEuidOfUname(string $uname): ?string
    {
        if(Encoders::isUnameFormatValid($uname) || Encoders::isEuidFormatValid($uname)) {
            if($this->isHydrateMode()) {
                $unameOjb = $this->getNormaliserService()->findCreated($uname);
            }
            $unameOjb ??= $this->getRepository(Uname::class)->findOneById($uname);
            if($unameOjb instanceof UnameInterface) {
                $euid = $unameOjb->getEntityEuid();
                if(Encoders::isEuidFormatValid($euid)) return $euid;
            }
        }
        return null;
    }

    public function findByUniqueValue(string $value): ?BaseEntityInterface
    {
        return Encoders::isEuidFormatValid($value)
            ? $this->findByEuid($value)
            : $this->findByUname($value);
    }

    public function getClassnameByUname(string $uname): ?string
    {
        if($this->isHydrateMode()) {
            $entity = $this->getNormaliserService()->findCreated($uname);
            $result = $entity ? $entity->getClassname() : $this->getNormaliserService()->tryFindCatalogueClassname($uname);
            if($result) return $result;
        }
        return $this->getRepository(Uname::class)->getClassnameByUname($uname);
    }

    public function getClassnameByEuidOrUname(string $euidOrUname): ?string
    {
        return Encoders::isEuidFormatValid($euidOrUname)
            ? Encoders::getClassOfEuid($euidOrUname)
            : $this->getClassnameByUname($euidOrUname);
    }

    public function findEntityByUname(string $uname): ?TraitUnamedInterface
    {
        if($this->isHydrateMode() && ($entity = $this->getNormaliserService()->findCreated($uname))) {
            return $entity;
        }
        $unameOjb = $this->getRepository(Uname::class)->findOneById($uname);
        if($unameOjb instanceof UnameInterface) {
            return $this->findByEuid($unameOjb->getEntityEuid());
        }
        return null;
    }


    /************************************************************************************************************/
    /** DATABASE REQUESTS                                                                                       */
    /************************************************************************************************************/

    public function count(string $classname, bool|array $criteria = []): int
    {
        if($service = $this->getEntityService($classname)) {
            return $service->count($criteria);
        }
        if(is_bool($criteria)) {
            $criteria = true === $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        /** @var BaseWireRepositoryInterface */
        $repo = $this->em->getRepository($classname);
        return $repo->count($criteria);
    }

    public function findAll(string $classname, bool|array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if($service = $this->getEntityService($classname)) {
            /** @var WireEntityServiceInterface $service */
            return $service->findAll($criteria, $orderBy, $limit, $offset);
        }
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        $entities = $this->getRepository($classname)->findBy($criteria, $orderBy, $limit, $offset);
        return array_filter($entities, function ($entity) {
            if ($entity instanceof TraitEnabledInterface) {
                return $entity->isActive();
            }
            return true;
        });
    }

    public function findOneBy(string $classname, int|string $identifier, bool|array $criteria = [], ?array $orderBy = null): ?object
    {
        if($service = $this->getEntityService($classname)) {
            return $service->findOneBy($identifier, $criteria, $orderBy);
        }
        if(is_bool($criteria)) {
            $criteria = $criteria ? static::getCriteriaEnabled($classname) : static::getCriteriaDisabled($classname);
        }
        if(is_int($identifier) && $identifier > 0) {
            $criteria['id'] = $identifier;
        } else if(Encoders::isEuidFormatValid($identifier)) {
            $criteria['euid'] = $identifier;
        } elseif(Encoders::isUnameFormatValid($identifier)) {
            $euid = $this->getEuidOfUname($identifier);
            if(empty($euid)) {
                throw new Exception(vsprintf('Error %s line %d: could not resolve euid with uname %s for class %s!', [__METHOD__, __LINE__, $identifier, $classname]));
            }
            $criteria['euid'] = $euid;
        } else {
            throw new Exception(vsprintf('Error %s line %d: identifier "%s" is not valid!', [__METHOD__, __LINE__, $identifier]));
        }
        // $criteria_object = Criteria::create();
        // foreach ($criteria as $key => $value) {
        //     $criteria_object->andWhere(Criteria::expr()->eq($key, $value));
        // }
        $entity = $this->getRepository($classname)->findOneBy($criteria, $orderBy);
        if($entity instanceof TraitEnabledInterface) {
            return $entity->isActive() ? $entity : null;
        }
        return $entity;
    }


    /************************************************************************************************************/
    /** CRITERIA                                                                                                */
    /************************************************************************************************************/

    public static function getCriteriaEnabled(string $classname): array
    {
        return is_a($classname, TraitEnabledInterface::class, true) ? static::CRITERIA_ENABLED : [];
        // return is_a($classname, TraitEnabledInterface::class, true) ? [Criteria::expr()->eq('enabled', true)] : [];
    }

    public static function getCriteriaDisabled(string $classname): array
    {
        return is_a($classname, TraitEnabledInterface::class, true) ? static::CRITERIA_DISABLED : [];
        // return is_a($classname, TraitEnabledInterface::class, true) ? [Criteria::expr()->eq('enabled', false)] : [];
    }


    /************************************************************************************************************/
    /** ENTITY INFO                                                                                             */
    /************************************************************************************************************/

    public function addPostFlushInfos(PostFlushEventArgs $args): void
    {
        $this->postFlushInfos[] = $args->getObjectManager();
    }

    public function getPostFlushInfos(bool $getLastOnly = false): array
    {
        return $getLastOnly ? end($this->postFlushInfos) : $this->postFlushInfos;
    }

    /**
     * get entities [Wire]Metadata
     * 
     * @return WireClassMetadataManagerInterface
     */
    public function getEntitiesMetadata(): WireClassMetadataManagerInterface
    {
        return $this->entitiesMetadata ??= new WireClassMetadataManager($this);
    }

    public function getEntityMetadata(string|object $objectOrClass): WireClassMetadataInterface
    {
        return $this->getEntitiesMetadata()->getWireClassMetadata($objectOrClass);
    }


    /************************************************************************************************************/
    /** VICH IMAGE / LIIP IMAGE                                                                                 */
    /************************************************************************************************************/

    /**
     * get browser path
     * 
     * @param WireImageInterface|WirePdfInterface $media
     * @param string|null $filter
     * @param array $runtimeConfig
     * @param mixed $resolver
     * @param int $referenceType
     * @return string|null
     */
    public function getBrowserPath(WireImageInterface|WirePdfInterface $media, ?string $filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): ?string
    {
        $browserPath = $this->vichHelper->asset($media);
        if ($filter && !($media instanceof WirePdfInterface)) {
            $browserPath = $this->liipCache->getBrowserPath($browserPath, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        return $browserPath;
    }


}
