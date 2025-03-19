<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\interface\WireCategoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\trait\TraitBaseEntityService;
use Aequation\WireBundle\Service\trait\TraitBaseService;
// Symfony
use Knp\Component\Pager\PaginatorInterface;

abstract class WireCategoryService implements WireCategoryServiceInterface
{
    USE TraitBaseService;
    use TraitBaseEntityService;
    
    public const ENTITY_CLASS = WireCategoryInterface::class;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService,
        protected PaginatorInterface $paginator,
    ) {
    }


    public function getCategoryTypeChoices(
        bool $asHtml = false,
        bool $allnamespaces = false,
        bool $onlyInstantiables = true
    ): array
    {
        // $list = $this->wireEntityService->getEntityClassesOfInterface(WireCategoryInterface::class, false, $onlyInstantiables);
        // $class = reset($list);
        $class = static::ENTITY_CLASS;
        if(!empty($class)) {
            $relateds = $this->wireEntityService->getRelateds($class, null, false);
            $entities = $asHtml
                ? $this->wireEntityService->getEntityNamesChoices(true, true, $allnamespaces, $onlyInstantiables)
                : $this->wireEntityService->getEntityNames(false, $allnamespaces, $onlyInstantiables);
            $list = array_filter(
                $entities,
                function($class) use ($relateds) {
                    // return !is_a($class, static::class, true);
                    return array_key_exists($class, $relateds);
                }
            );
        }
        return $list;
    }

}