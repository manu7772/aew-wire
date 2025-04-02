<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\TraitOwnerInterface;
use Aequation\WireBundle\Entity\interface\TraitPreferedInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireDatabaseCheckerInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireServiceInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Class WireDatabaseChecker
 * @package Aequation\WireBundle\Service
 */
#[AsAlias(WireDatabaseCheckerInterface::class, public: false)]
#[Autoconfigure(autowire: true, lazy: true)]
class WireDatabaseChecker implements WireDatabaseCheckerInterface
{

    use TraitBaseService;

    public readonly EntityManagerInterface $em;

    /**
     * constructor.
     * 
     * @param EntityManagerInterface $em
     * @param AppWireServiceInterface $appWire
     * @param UploaderHelper $vichHelper
     * @param CacheManager $liipCache
     */
    public function __construct(
        public readonly AppWireServiceInterface $appWire,
        public readonly WireEntityManagerInterface $wireEm,
        public readonly WireUserServiceInterface $userService,
    ) {
        $this->em = $this->wireEm->getEntityManager();
        // $this->uow = $this->em->getUnitOfWork();
    }


    /****************************************************************************************************/
    /** MAINTAIN DATABASE                                                                               */
    /****************************************************************************************************/

    public function checkAllDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult ??= new Opresult();
        foreach ($this->wireEm->getEntityNames(false, false, true) as $classname) {
            $this->checkDatabase($classname, $opresult, $repair);
        }
        return $opresult;
    }

    public function checkDatabase(
        string $classname,
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult ??= new Opresult();
        // Check prefered
        if(is_a($classname, TraitPreferedInterface::class, true)) {
            $this->database_check_prefered($classname, $opresult, $repair);
        }
        // Check Owner
        if(is_a($classname, TraitOwnerInterface::class, true)) {
            $this->database_check_owner($classname, $opresult, $repair);
        }
        // Check others...
        // ...
        // Check specific functionalities for entity
        if($service = $this->wireEm->getEntityService($classname)) {
            /** @var WireEntityServiceInterface $service */
            $service->checkDatabase($opresult, $repair);
        }
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    /**
     * Check TraitPreferedInterface
     */
    public function database_check_prefered(
        string $classname,
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult ??= new Opresult();
        if(is_a($classname, TraitPreferedInterface::class, true)) {
            $repo = $this->wireEm->getRepository($classname);
            $prefered = $repo->findBy(['prefered' => true]);
            if(count($prefered) > 1) {
                $opresult->addDanger(vsprintf('Error %s line %d: %s has more than one prefered (found %d)!', [__METHOD__, __LINE__, $classname, count($prefered)]));
                if($repair) {
                    $prefered = array_slice($prefered, 1);
                    foreach ($prefered as $entity) {
                        $entity->setPrefered(false);
                    }
                    $this->em->flush();
                    $this->em->clear();
                    foreach ($prefered as $entity) {
                        unset($entity);
                    }
                    $prefered = $repo->findBy(['prefered' => true]);
                    if(count($prefered) > 1) {
                        $opresult->addDanger(vsprintf('Error %s line %d: %s has more than one prefered after repair (still found %d)!', [__METHOD__, __LINE__, $classname, count($prefered)]));
                    } else {
                        $opresult->addSuccess(vsprintf('Success %s line %d: %s has been repaired!', [__METHOD__, __LINE__, $classname]));
                        foreach ($prefered as $entity) {
                            unset($entity);
                        }
                    }
                }
            }
        }
        $this->wireEm->decDebugMode();
        return $opresult;
    }

    /**
     * Check TraitOwnerInterface
     */
    public function database_check_owner(
        string $classname,
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $this->wireEm->incDebugMode();
        $opresult ??= new Opresult();
        if(is_a($classname, TraitOwnerInterface::class, true)) {
            $repo = $this->wireEm->getRepository($classname);
            $owners = $repo->findBy(['owner' => null]);
            $count = 0;
            foreach ($owners as $owner) {
                /** @var TraitOwnerInterface $owner */
                if($owner->isOwnerRequired()) {
                    $opresult->addDanger(vsprintf('Error %s line %d: no owner found for %s!', [__METHOD__, __LINE__, $classname, Objects::toDebugString($owner)]));
                    if($repair) {
                        // Attribute main admin as owner by default
                        /** @var WireUserServiceInterface */
                        $userService ??= $this->appWire->get(WireUserServiceInterface::class);
                        $admin ??= $userService->getMainAdminUser();
                        if(empty($admin)) {
                            $opresult->addDanger(vsprintf('Error %s line %d: no main admin found!', [__METHOD__, __LINE__]));
                        } else {
                            $owner->setOwner($admin);
                            $count++;
                            $opresult->addSuccess(vsprintf('Success %s line %d: %s has been repaired!', [__METHOD__, __LINE__, Objects::toDebugString($owner)]));
                        }
                    }
                }
            }
            if($count > 0) {
                $this->em->flush();
                $this->em->clear();
                foreach ($owners as $owner) {
                    unset($owner);
                }
            }
        }
        $this->wireEm->decDebugMode();
        return $opresult;
    }



}