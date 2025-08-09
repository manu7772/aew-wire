<?php
namespace Aequation\WireBundle\Dto\transform;


class EntityTransformToDto
{
    /**
     * Transforms the given entity to a Dto.
     *
     * @param mixed $dto The Dto to transform.
     * @param mixed $entity The entity from which to transform.
     * @return mixed The transformed dto.
     */
    public static function transform(mixed $dto, mixed $entity): mixed
    {
        // Implement transformation logic here
        return $dto;
    }
}