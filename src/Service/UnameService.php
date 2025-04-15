<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Component\Opresult;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
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