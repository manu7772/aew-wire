<?php
namespace Aequation\WireBundle\Dto\transform;

use Aequation\WireBundle\Entity\interface\WireUserInterface;

class WireUserTransformFromDto extends EntityTransformFromDto
{
    /**
     * Transforms the given Dto from an entity.
     *
     * @param mixed $entity The entity to transform.
     * @param mixed $dto The Dto from which to transform.
     * @return mixed The transformed entity.
     */
    public static function transform(mixed $entity, mixed $dto): mixed
    {
        parent::transform($entity, $dto);
        if($entity instanceof WireUserInterface) {
            if($dto->superadmin) {
                $entity->setSuperadmin();
            }
        }
        return $entity;
    }
}