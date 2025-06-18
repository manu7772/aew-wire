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
        $routeParams = $request->attributes->get('_route_params');
        $routeMapping = $request->attributes->get('_route_mapping', []);
        if(empty($routeMapping)) {
            $uids = [];
            foreach ($routeParams as $key => $value) {
                if(preg_match('/^(?!_).+$/', $key)) {
                    $uids[$key] = $key;
                }
            }
            // If no route mapping is set, use the default mapping if available
            if(count($routeParams) === 1 && count($uids) === 1) {
                $routeMapping = [reset($uids) => $argument->getName()];
            } else {
                throw new RuntimeException(vsprintf('Error %s line %d: No route mapping found for type "%s", please write this in the route path (something like this: /paht/{id:entity}).', [__METHOD__, __LINE__, $argument->getType()]));
            }
            $uid ??= array_key_first($routeMapping);
        }
        $uid ??= reset($routeMapping);
        $argumentName = reset($routeMapping);
        $classname = $repository->getEntityName();
        // dump($routeMapping, $argument, $request->attributes->all(), $argumentName, $routeParams, $classname, $uid);
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
        if($this->wireEm->appWire->isDev()) {
            dump(vsprintf('RouteParam {%s} of value %s: found %s put in method param $%s of type %s.', [$uid, $request->attributes->get($argumentName, '-- not found --'), Objects::toDebugString($value), $argumentName, $classname]));
        }
        if(empty($value)) {
            // If no value found, return null
            throw new RuntimeException(vsprintf('Error %s line %d: No value found for type "%s" with identifier "%s".', [__METHOD__, __LINE__, $argument->getType(), $request->attributes->get($argumentName, '-- not found --')]));
        }
        return [$argumentName => $value];
    }
}