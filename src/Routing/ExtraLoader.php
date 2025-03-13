<?php

namespace Aequation\WireBundle\Routing;

use Aequation\WireBundle\Controller\API\AppWireController;
use Aequation\WireBundle\Controller\EntityAdminController;
use Aequation\WireBundle\Controller\RegistrationController;
use Aequation\WireBundle\Controller\SecurityController;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use ReflectionClass;
use ReflectionMethod;
// Symfony
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
// PHP
use RuntimeException;
use Symfony\Component\Routing\Attribute\Route as AttributeRoute;

class ExtraLoader implements LoaderInterface
{
    private $loaded = false;
    private ?LoaderResolverInterface $loderResolver;
    private array $routenames = [];

    public function __construct(
        private WireEntityManagerInterface $wire_em
    ) {}

    public function load($resource, $type = null): mixed
    {
        if (true === $this->loaded) {
            throw new RuntimeException(vsprintf('Error %s line %d: Do not add the "%s" loader twice', [__METHOD__, __LINE__, __CLASS__]));
        }

        $routes = new RouteCollection();

        // Darkmode Switcher
        $path = '/api/darkmode/{darkmode}';
        $defaults = [
            '_controller' => AppWireController::class . '::darkmodeSwitcher',
            'darkmode' => 'auto',
        ];
        $requirements = ['darkmode' => '/(on|off|auto)/'];
        $methods = ['GET', 'POST'];
        $route = new Route(path: $path, defaults: $defaults, requirements: $requirements, methods: $methods);
        $this->routenames['aequation_wire_api.darkmode_switcher'] = $route;
        $routes->add('aequation_wire_api.darkmode_switcher', $route);

        // Security
        // Login
        $path = '/login';
        $defaults = ['_controller' => SecurityController::class . '::login'];
        $route = new Route(path: $path, defaults: $defaults);
        $this->routenames['app_login'] = $route;
        $routes->add('app_login', $route);
        // Logout
        $path = '/logout';
        $defaults = ['_controller' => SecurityController::class . '::logout'];
        $route = new Route(path: $path, defaults: $defaults);
        $this->routenames['app_logout'] = $route;
        $routes->add('app_logout', $route);
        // Register
        $path = '/register';
        $defaults = ['_controller' => RegistrationController::class . '::register'];
        $route = new Route(path: $path, defaults: $defaults);
        $this->routenames['app_register'] = $route;
        $routes->add('app_register', $route);

        // Admin entities
        $reflectionClass = new ReflectionClass(EntityAdminController::class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $attributes = $method->getAttributes(AttributeRoute::class);
            foreach ($attributes as $attribute) {
                $routeAttr = $attribute->newInstance();
                $route = new Route(
                    path: $routeAttr->getPath(),
                    defaults: $routeAttr->getDefaults(),
                    requirements: $routeAttr->getRequirements(),
                    options: $routeAttr->getOptions(),
                    host: $routeAttr->getHost(),
                    schemes: $routeAttr->getSchemes(),
                    methods: $routeAttr->getMethods(),
                    condition: $routeAttr->getCondition()
                );
                $this->routenames[$routeAttr->getName()] = $route;
                $routes->add($routeAttr->getName(), $route);
            }
        }
        $this->loaded = true;
        // dump(array_keys($this->routenames));
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
