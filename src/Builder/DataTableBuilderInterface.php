<?php
namespace Aequation\WireBundle\Builder;

use Aequation\WireBundle\Model\DataTable;

interface DataTableBuilderInterface
{

    public function createDataTable(?string $id = null): DataTable;

}