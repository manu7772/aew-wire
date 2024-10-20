<?php
declare(strict_types=1);
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vich\UploaderBundle\Exception\MappingNotFoundException;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\ConfigurableInterface;
use Vich\UploaderBundle\Util\ClassUtils;
// Override
use Vich\UploaderBundle\Mapping\PropertyMappingResolver;
use Vich\UploaderBundle\Mapping\PropertyMappingResolverInterface;

/**
 * !!!Override PropertyMappingResolver::class!!!
 * PropertyMappingResolver.
 *
 * @author Dustin Dobervich <ddobervich@gmail.com>
 *
 * @internal
 */
#[AsDecorator(decorates: 'vich_uploader.property_mapping_resolver')]
class DecoratedPropertyMappingResolver implements PropertyMappingResolverInterface
{

    public function __construct(
        #[AutowireDecorated]
        private object $inner,
        // private readonly ContainerInterface $container,
        // private readonly array $mappings,
        // private readonly ?string $defaultFilenameAttributeSuffix = '_name'
    ) {
    }

    public function resolve(object|array $obj, string $fieldName, array $mappingData): PropertyMapping
    {
        if (!\array_key_exists($mappingData['mapping'], $this->inner->mappings)) {
            $className = \is_object($obj) ? ClassUtils::getClass($obj) : '[array]';
            throw MappingNotFoundException::createNotFoundForClassAndField($mappingData['mapping'], $className, $fieldName);
        }

        $appWire = $this->inner->container->get(AppWireServiceInterface::class);
        if($appWire->isDev()) {
            // DEBUG HERE!
            dd(vsprintf('DEBUG HERE (%s line %d) ClassMetadata:', [__METHOD__, __LINE__]), $this);
        }

        $config = $this->inner->mappings[$mappingData['mapping']];
        $fileProperty = $mappingData['propertyName'] ?? $fieldName;
        $fileNameProperty = empty($mappingData['fileNameProperty']) ? $fileProperty.$this->inner->defaultFilenameAttributeSuffix : $mappingData['fileNameProperty'];

        $mapping = new PropertyMapping($fileProperty, $fileNameProperty, $mappingData);
        $mapping->setMappingName($mappingData['mapping']);
        $mapping->setMapping($config);

        if (!empty($config['namer']) && null !== $config['namer']['service']) {
            $namerConfig = $config['namer'];
            $namer = $this->inner->container->get($namerConfig['service']);

            if (!empty($namerConfig['options'])) {
                if (!$namer instanceof ConfigurableInterface) {
                    throw new \LogicException(\sprintf('Namer %s can not receive options as it does not implement ConfigurableInterface.', $namerConfig['service']));
                }
                $namer->configure($namerConfig['options']);
            }

            $mapping->setNamer($namer);
        }

        if (!empty($config['directory_namer']) && null !== $config['directory_namer']['service']) {
            $namerConfig = $config['directory_namer'];
            $namer = $this->inner->container->get($namerConfig['service']);

            if (!empty($namerConfig['options'])) {
                if (!$namer instanceof ConfigurableInterface) {
                    throw new \LogicException(\sprintf('Namer %s can not receive options as it does not implement ConfigurableInterface.', $namerConfig['service']));
                }
                $namer->configure($namerConfig['options']);
            }

            $mapping->setDirectoryNamer($namer);
        }

        return $mapping;
    }
}
