<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Entity\interface\WireEntityInterface;
use Aequation\WireBundle\Repository\interface\BaseWireRepositoryInterface;

interface WireEntityManagerInterface extends WireServiceInterface
{

    public const CLONE_METHOD_WIRE = 0;
    public const CLONE_METHOD_WITH = 1;
    public const CLONE_METHOD_WILD = 2;

    public function getAppWireService(): AppWireServiceInterface;
    public function getEntityService(string|WireEntityInterface $entity): WireEntityManagerInterface|WireEntityServiceInterface;
    public static function getConstraintUniqueFields(string $classname, bool|null $flatlisted = false): array;
    public function getRepository(string $classname, string $field = null): BaseWireRepositoryInterface;
}