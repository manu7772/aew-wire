<?php
namespace Aequation\WireBundle\ValueResolver;

use Aequation\WireBundle\Entity\interface\SluggableInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
use RuntimeException;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver('app_entity_value_resolver')]
class AppEntityValueResolver implements ValueResolverInterface
{

    public function __construct(
        private WireEntityManagerInterface $wireEm
    )
    {
        // Constructor logic if needed
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Find final entity class
        // $entityClass = $this->wireEm->resolveFinalEntitiesByNames($argument->getType());
        $repository = $this->wireEm->getRepository($argument->getType());
        if (!$repository) {
            throw new RuntimeException(vsprintf('Error %s line %d: No repository/service found for type "%s".', [__METHOD__, __LINE__, $argument->getType()]));
        }
        // dd($argument, $argument->isVariadic(), $request->attributes->get('id'), $repository->getEntityName(), $request);
        $routParams = $request->attributes->get('_route_params');
        $uid = array_key_first($routParams);
        $classname = $repository->getEntityName();
        switch (true) {
            case ($identifier = intval($request->attributes->get($uid, 0))) > 0:
                // Find by ID
                $value = $repository->find($identifier);
                break;
            case !empty($identifier = $request->attributes->getString($uid)) && is_a($classname, SluggableInterface::class, true):
                // Find by SLUG
                $value = $repository->findOneBy(['slug' => $identifier]);
                if(empty($value) && is_a($classname, TraitUnamedInterface::class, true)) {
                    // Slug not found, try to find by UNAME
                    $value = $this->wireEm->findEntityByUname($identifier);
                }
                break;
            default:
                $value = null;
                // throw new RuntimeException(vsprintf('Error %s line %d: No valid identifier (id%s) found for type "%s".', [__METHOD__, __LINE__, is_a($classname, SluggableInterface::class, true) ? ' or slug' : '', $argument->getType()]));
                break;
        }

        return [$argument->getName() => $value];
    }
}