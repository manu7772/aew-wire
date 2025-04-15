<?php
namespace Aequation\WireBundle\Doctrine\Type;

use Aequation\WireBundle\Component\ArrayTextUtil;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * ArrayTextType Type
 * - copied from Doctrine\DBAL\Types\JsonType
 * 
 * @see https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#custom-mapping-types
 * @see https://symfony.com/doc/current/doctrine/dbal.html#registering-custom-mapping-types
 * 
 */
class ArrayTextType extends Type
{
    public const NAME = 'arraytext';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // return $platform->getClobTypeDeclarationSQL($column);
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value->jsonSerialize();
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        // if(is_resource($value)) $value = stream_get_contents($value);
        return new ArrayTextUtil(is_resource($value) ? stream_get_contents($value) : $value);
    }

    public function getName()
    {
        return self::NAME;
    }

}