<?php
namespace Aequation\WireBundle\Builder\interface;

use Aequation\WireBundle\Model\DataTable;

interface DataTableBuilderInterface
{

    public function createDataTable(?string $id = null): DataTable;

}