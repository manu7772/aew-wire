<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Entity\WireItem;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Service\interface\WireItemServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias(WireItemServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class WireItemService extends BaseService implements WireItemServiceInterface
{
    public const ENTITY_CLASS = WireItem::class;

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


}