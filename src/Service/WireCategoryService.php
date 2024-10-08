<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireCategory;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireCategoryServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(WireCategoryServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class WireCategoryService extends BaseService implements WireCategoryServiceInterface
{
    public const ENTITY_CLASS = WireCategory::class;

    protected BaseWireRepositoryInterface $repository;

    public function __construct(
        protected AppWireServiceInterface $appWire,
        protected WireEntityManagerInterface $wireEntityService
    )
    {}

    public function getRepository(): BaseWireRepositoryInterface
    {
        return $this->repository ??= $this->wireEntityService->getRepository(static::ENTITY_CLASS);
    }


    public function getCategoryTypeChoices(): array
    {
        $choices = [];
        
        return $choices;
    }

}