<?php
namespace Aequation\WireBundle\Service;

use Aequation\WireBundle\Service\interface\WireArticleServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

// #[AsAlias(WireArticleServiceInterface::class, public: true)]
// #[Autoconfigure(autowire: true, lazy: true)]
abstract class WireArticleService extends WireItemService implements WireArticleServiceInterface
{

}