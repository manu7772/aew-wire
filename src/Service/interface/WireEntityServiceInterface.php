<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;

interface WireEntityServiceInterface extends WireServiceInterface
{

    public function getEntityClassname(): ?string;
    public function getRepository(): BaseWireRepositoryInterface;
    public function getEntitiesCount(array $criteria = []): int;

}