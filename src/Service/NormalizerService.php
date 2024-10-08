<?php
namespace Aequation\WireBundle\Service;

// Symfony
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
// PHP
use ArrayObject;
use Doctrine\Common\Collections\Collection;

class NormalizerService
{

    public function __construct(
        public readonly NormalizerInterface $normalizer
    )
    {
        
    }

    /****************************************************************************************************/
    /** NORMALIZER / SERIALIZER                                                                         */
    /****************************************************************************************************/

    public function getNormalized(
        mixed $object,
        ?string $format = null,
        array $context = [],
        bool $convertToArrayList = false
    ): array|string|int|float|bool|ArrayObject|null
    {
        if($object instanceof Collection) $object = $object->toArray();
        if($convertToArrayList && is_array($object) && !array_is_list($object)) $object = array_values($object); // for React, can not be object, but array
        return $this->normalizer->normalize($object, $format, $context);
    }



}