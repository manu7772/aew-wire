<?php
namespace Aequation\WireBundle\ValueResolver;

use Aequation\WireBundle\Entity\interface\SluggableInterface;
use Aequation\WireBundle\Entity\interface\TraitUnamedInterface;
use Aequation\WireBundle\Service\interface\AppWireServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
use ReflectionClassConstant;
use RuntimeException;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsTargetedValueResolver('app_entity_value_resolver')]
class AppEntityValueResolver implements ValueResolverInterface
{

    public function __construct(
        // private readonly AppWireServiceInterface $appWire,
        private readonly WireEntityManagerInterface $wireEm
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
        $classes = $this->wireEm->getEntitiesMetadata()->findFinals([$argument->getType()]);
        if(empty($classes)) {
            throw new RuntimeException(vsprintf('Error %s line %d: Expected exactly one final class for type "%s", found %d.', [__METHOD__, __LINE__, $argument->getType(), count($classes)]));
        } else if(count($classes) > 1) {
            // If more than one class found, try find out by Controller data
            $controller = $request->attributes->get('_controller');
            if(!is_string($controller) || !str_contains($controller, '::')) {
                throw new RuntimeException(vsprintf('Error %s line %d: Expected controller to be a string with "Class::method" format, found "%s".', [__METHOD__, __LINE__, Objects::toDebugString($controller)]));
            }
            $controllerParts = explode('::', $controller);
            $rconstant = new ReflectionClassConstant($controllerParts[0], 'ENTITY_CLASS');
            $entity_class = $rconstant->getValue();
        } else {
            $entity_class = $argument->getType();
        }
        $repository = $this->wireEm->getRepository($entity_class);
        if (!$repository) {
            throw new RuntimeException(vsprintf('Error %s line %d: No repository/service found for type "%s".', [__METHOD__, __LINE__, $entity_class]));
        }
        $routeParams = $request->attributes->get('_route_params');
        $routeMapping = $request->attributes->get('_route_mapping', []);
        $params_values = array_filter($request->attributes->all(), function($key) {
            return !str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);
        // if(is_array($routeMapping)) {
        //     dd($routeMapping, $routeParams);
        // }
        if(empty($routeMapping)) {
            $uids = [];
            foreach ($routeParams as $key => $value) {
                if(preg_match('/^(?!_).+$/', $key)) {
                    $uids[$key] = $key;
                }
            }
            // If no route mapping is set, use the default mapping if available
            if(count($routeParams) === 1 && count($uids) === 1) {
                // $routeMapping = [reset($uids) => $argument->getName()];
                $uid = reset($uids);
                $argumentName = $argument->getName();
            } else {
                throw new RuntimeException(vsprintf('Error %s line %d: No route mapping found for type "%s", please write this in the route path (something like this: /path/to/{id:entity}).', [__METHOD__, __LINE__, $entity_class]));
            }
            // $uid ??= array_key_first($routeMapping);
        } else {
            $uid = array_key_first($routeMapping);
            $first = $routeMapping[$uid];
            if(is_array($first)) {
                foreach($first as $name) {
                    if($name !== $uid) {
                        $argumentName = $name;
                        break;
                    }
                }
            } else {
                $argumentName = $first;
            }
        }
        // $uid ??= reset($routeMapping);
        // $argumentName = reset($routeMapping);
        $classname = $repository->getEntityName();
        $param_value = $params_values[$argumentName][$uid];
        // dd($uid, $argumentName, $value, $params_values);
        // dd($routeMapping, $argument, $request->attributes->all(), $argumentName, $routeParams, $classname, $uid);
        switch (true) {
            case intval($param_value) > 0:
                // Find by ID
                $value = $repository->find($param_value);
                break;
            case !empty($param_value) && is_a($classname, SluggableInterface::class, true):
                // Find by SLUG
                $value = $repository->findOneBy(['slug' => $param_value]);
                if(empty($value) && is_a($classname, TraitUnamedInterface::class, true)) {
                    // Slug not found, try to find by UNAME
                    $value = $this->wireEm->findEntityByUname($param_value);
                }
                break;
            default:
                $value = null;
                // throw new RuntimeException(vsprintf('Error %s line %d: No valid identifier (id%s) found for type "%s".', [__METHOD__, __LINE__, is_a($classname, SluggableInterface::class, true) ? ' or slug' : '', $entity_class]));
                break;
        }
        // if($this->wireEm->appWire->isDev()) {
        //     dump(vsprintf('RouteParam {%s} of value %s: found %s put in method param $%s of type %s.', [$uid, $request->attributes->get($argumentName, '-- not found --'), Objects::toDebugString($value), $argumentName, $classname]));
        // }
        if(empty($value)) {
            // If no value found, return null
            throw new NotFoundHttpException(vsprintf('Error %s line %d: No value found for type "%s" with identifier "%s".', [__METHOD__, __LINE__, json_encode($entity_class), json_encode($request->attributes->get($argumentName, '-- not found --'))]));
        }
        return [$argumentName => $value];
    }
}