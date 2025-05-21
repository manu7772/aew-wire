<?php
namespace Aequation\WireBundle\Routing;

use Aequation\WireBundle\Controller\API\AppWireController;
use Aequation\WireBundle\Controller\EntityAdminController;
use Aequation\WireBundle\Controller\RegistrationController;
use Aequation\WireBundle\Controller\SecurityController;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Attribute\Route as AttributeRoute;
// PHP
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Route ExtraLoader
 * 
 * Please add the following to your config/routes/framework.yaml file:
 *
 * AequationWireBundle_Extra:
 *      resource: .
 *      type: extra
 * 
 * @see https://symfony.com/doc/current/routing/custom_route_loader.html
 * @see https://symfony-docs-zh-cn.readthedocs.io/cookbook/routing/custom_route_loader.html
 */
class ExtraLoader implements LoaderInterface
{
    private ?LoaderResolverInterface $loderResolver;
    private $loaded = false;
    private array $controllers;

    // public function __construct(
    //     private WireEntityManagerInterface $wire_em
    // ) {}

    /**
     * Get all controllers Route informations in the Aequation\WireBundle\Controller namespace
     * 
     * @return array
     */
    public function getControllersList(): array
    {
        if(isset($this->controllers)) {
            return $this->controllers;
        }
        $this->controllers = [];
        foreach(get_declared_classes() as $class) {
            if (preg_match('/^Aequation\\\WireBundle\\\Controller\\\/', $class)) {
                $reflectionClass = new ReflectionClass($class);
                $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
                $this->controllers[$class] = [
                    'reflectionClass' => $reflectionClass,
                    'reflectionMethods' => $methods,
                    'classRoutes' => [],
                    'attributeRoutes' => [],
                    'routes' => [],
                ];
                // Class Routes
                $prefix_routename = null;
                $prefix_path = null;
                $attributes = $reflectionClass->getAttributes(AttributeRoute::class);
                foreach ($attributes as $attribute) {
                    // Add the Class Route attribute
                    $routeAttr = $attribute->newInstance();
                    $this->controllers[$class]['classRoutes'][] = $routeAttr;
                    $prefix_routename ??= $routeAttr->getName();
                    $prefix_path ??= $routeAttr->getPath();
                }
                // Method Routes
                foreach ($methods as $method) {
                    $attributes = $method->getAttributes(AttributeRoute::class);
                    foreach ($attributes as $attribute) {
                        // Add the Route attribute
                        $routeAttr = $attribute->newInstance();
                        $this->controllers[$class]['attributeRoutes'][] = $routeAttr;
                        $routename = $prefix_routename.$routeAttr->getName();
                        if (isset($this->controllers[$class]['routes'][$routename])) {
                            throw new RuntimeException(vsprintf('Error %s line %d: Route name "%s" already exists in class "%s"', [__METHOD__, __LINE__, $routename, $class.'::'.$method->getName()]));
                        }
                        $this->controllers[$class]['routes'][$routename] = new Route(
                            path: $prefix_path.$routeAttr->getPath(),
                            defaults: array_merge(['_controller' => $class.'::'.$method->getName()], $routeAttr->getDefaults()),
                            requirements: $routeAttr->getRequirements(),
                            options: $routeAttr->getOptions(),
                            host: $routeAttr->getHost(),
                            schemes: $routeAttr->getSchemes(),
                            methods: $routeAttr->getMethods(),
                            condition: $routeAttr->getCondition()
                        );
                    }
                }
            }
        }
        return $this->controllers;
    }

    public function load($resource, $type = null): mixed
    {
        if (true === $this->loaded) {
            throw new RuntimeException(vsprintf('Error %s line %d: Do not add the "%s" loader twice', [__METHOD__, __LINE__, __CLASS__]));
        }
        $routes = new RouteCollection();
        foreach ($this->getControllersList() as $data) {
            foreach ($data['routes'] as $routename => $route) {
                $routes->add($routename, $route);
            }
        }
        $this->loaded = true;
        // dump($routes);
        return $routes;
    }

    public function supports($resource, $type = null): bool
    {
        return 'extra' === $type;
    }

    public function getResolver(): LoaderResolverInterface
    {
        // needed, but can be blank, unless you want to load other resources
        // and if you do, using the Loader base class is easier (see below)
        return $this->loderResolver;
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        // same as above
        $this->loderResolver = $resolver;
    }
}
