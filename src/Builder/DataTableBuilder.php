<?php
namespace Aequation\WireBundle\Builder;

use Aequation\WireBundle\Builder\interface\DataTableBuilderInterface;
use Aequation\WireBundle\Model\DataTable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('datatable.builder')]
class DataTableBuilder implements DataTableBuilderInterface
{

    public function createDataTable(
        ?string $id = null
    ): DataTable
    {
        return new DataTable($id);
    }

}