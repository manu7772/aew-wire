<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Knp\Component\Pager\PaginatorInterface;
// PHP
use Exception;

#[AsAlias(UnameServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class UnameService implements UnameServiceInterface
{
    USE TraitBaseService;
    use TraitBaseEntityService;

    public const ENTITY_CLASS = Uname::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEm,
        protected PaginatorInterface $paginator,
        public readonly NormalizerServiceInterface $normalizer,
    ) {
    }

    public function checkDatabase(
        ?OpresultInterface $opresult = null,
        bool $repair = false
    ): OpresultInterface
    {
        $opresult ??= new Opresult();
        // Check all UnameInterface entities
        return $opresult;
    }

    public function findOrphanUnames(): array
    {
        $repository = $this->wireEm->getRepository(Uname::class);
        $unames = $repository
            ->createQueryBuilder('u')
            ->select('u.id, u.euid, u.entityEuid, u.classname, u.shortname')
            ->getQuery()
            ->getScalarResult()
            ;
        return array_filter($unames, fn($uname) => !$this->wireEm->entityWithEuidExists($uname['entityEuid'], false));
    }

    public function removeOrphanUnames(
        null|array|string|UnameInterface $unames = null
    ): OpresultInterface
    {
        $opresult = new Opresult();
        if(empty($unames)) {
            // Select all orphan unames
            $unames = array_map(fn($uname) => $uname['euid'], $this->findOrphanUnames());
        }
        $unames = $unames instanceof UnameInterface ? [$unames] : (array)$unames;
        foreach ($unames as $uname) {
            if(is_string($uname)) {
                $uname = $this->getRepository()->findOneByEuid($uname);
            }
            $messages = [];
            if($uname instanceof UnameInterface) {
                $entity = $this->wireEm->entityWithEuidExists($uname->getEntityEuid(), true);
                if(!empty($entity)) {
                    // Uname is not orphan!
                    $opresult->addWarning(vsprintf('Uname %s is not orphan!', [$uname->getId().'-'.$uname->getEuid()]));
                } else {
                    $this->wireEm->getEm()->remove($uname);
                    // $opresult->incCounter('deleted');
                    $messages[] = vsprintf('Uname %s removed! (entity searched: %s)', [$uname->getId().'-'.$uname->getEntityEuid().'-'.$uname->getEuid(), Objects::toDebugString($entity)]);
                }
            } else {
                $opresult->addUndone(vsprintf('Uname %s not found!', [Objects::toDebugString($uname)]));
            }
        }
        try {
            $this->wireEm->getEm()->flush();
        } catch (\Throwable $th) {
            $opresult->addDanger(vsprintf('Error %s line %d: %s', [__METHOD__, __LINE__, 'Deletion of orphan unames failed!']));
            $opresult->addMessage('dev', vsprintf('Error %s line %d: %s', [__METHOD__, __LINE__, $th->getMessage()]));
        }
        if(!$opresult->isFail()) {
            foreach ($messages as $message) {
                $opresult->addSuccess($message);
            }
        }
        return $opresult;
    }


    /****************************************************************************************************/
    /** PAGINABLE                                                                                       */
    /****************************************************************************************************/

    // /**
    //  * Get paginated context data.
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    // public function getPaginatedContextData(
    //     ?Request $request = null
    // ): array
    // {
    //     // $request ??= $this->appWire->getRequest();
    //     throw new Exception(vsprintf('Method %s not implemented yet.', [__METHOD__]));
    // }

}