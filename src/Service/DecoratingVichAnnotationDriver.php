<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
// Symfony
// use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Metadata\ClassMetadata as JMSClassMetadata;
use Metadata\Driver\AdvancedDriverInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;
use Vich\UploaderBundle\Metadata\Driver\AnnotationDriver;
use Vich\UploaderBundle\Metadata\Driver\AttributeReader;

/**
 * Override Vich AnnotationDriver
 * @see https://symfony.com/doc/current/service_container/service_decoration.html
 * DEBUG
 * $ symfony console debug:container Aequation\WireBundle\Service\DecoratingVichAnnotationDriver
 */
#[AsDecorator(decorates: 'vich_uploader.metadata_driver.annotation')]
class DecoratingVichAnnotationDriver extends AnnotationDriver implements AdvancedDriverInterface
{

    public function __construct(
        // protected readonly AttributeReader $reader,
        // private readonly array $managerRegistryList,
        #[AutowireDecorated]
        private object $inner,
        protected AppWireServiceInterface $appWire
    ) {
        // parent::__construct($reader, $managerRegistryList);
    }

    public function loadMetadataForClass(\ReflectionClass $class): ?JMSClassMetadata
    {
        $classMetaData = parent::loadMetadataForClass($class);
        if($classMetaData instanceof JMSClassMetadata) {
            if($this->appWire->isDev()) {
                // DEBUG HERE!
                dd(
                    vsprintf('DEBUG HERE (%s line %d) ClassMetadata:', [__METHOD__, __LINE__]),
                    $classMetaData
                );
            }
        }
        return $classMetaData;
    }

}