<?php
namespace Aequation\WireBundle\Dto\transform;


class EntityTransformFromDto
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
        // Implement transformation logic here
        return $entity;
    }
}