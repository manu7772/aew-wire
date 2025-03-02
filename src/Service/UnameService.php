<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\UnameInterface;
use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Entity\Uname;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\UnameServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
use Exception;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;

#[AsAlias(UnameServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class UnameService implements UnameServiceInterface
{
    USE TraitBaseService;
    use TraitBaseEntityService;

    public const ENTITY_CLASS = Uname::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected PaginatorInterface $paginator,
        public readonly NormalizerServiceInterface $normalizer,
    ) {
    }

    /**
     * Get entity classname
     *
     * @return string|null
     */
    public function getEntityClassname(): ?string
    {
        return (string)static::ENTITY_CLASS;
    }

    /**
     * Check entity after any changes.
     *
     * @param WireEntityInterface $entity
     * @return void
     */
    public function checkEntity(
        WireEntityInterface $entity
    ): void
    {
        if($entity instanceof UnameInterface) {
            // Check here
        }
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