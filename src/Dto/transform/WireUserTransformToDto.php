<?php
namespace Aequation\WireBundle\Dto\transform;


class WireUserTransformToDto extends EntityTransformToDto
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
        parent::transform($dto, $entity);
        $dto->superadmin = $entity->isSuperadmin();
        return $dto;
    }
}