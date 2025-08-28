<?php
namespace Aequation\WireBundle\Service\interface;

use Aequation\WireBundle\Component\interface\PaginatedContextDataInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;

interface EntityServicePaginableInterface
{
    public function getPaginated(?int $page = null, ?string $method = null, array $parameters = []): PaginationInterface;
    public function getPaginatedContextData(array $options = []): PaginatedContextDataInterface;
}