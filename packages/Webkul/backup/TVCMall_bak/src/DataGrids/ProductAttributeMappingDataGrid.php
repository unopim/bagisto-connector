<?php

namespace Webkul\TVCMall\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProductAttributeMappingDataGrid extends DataGrid
{
    protected $sortColumn = 'id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('tvc_mall_product_attribute_mappings')
            ->addSelect(
                'id',
                'unopim_code',
                'tvc_mall_code',
            );

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('tvc_mall::app.mapping.product.datagrid.id'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'unopim_code',
            'label' => trans('tvc_mall::app.mapping.product.datagrid.unopim_code'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'tvc_mall_code',
            'label' => trans('tvc_mall::app.mapping.product.datagrid.tvc_mall_code'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('tvc_mall.product_mapping.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('tvc_mall::app.mapping.product.datagrid.delete-btn'),
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('tvc_mall.product-attribute-mapping.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare the mass actions
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('tvc_mall.product_mapping.delete')) {
            $this->addMassAction([
                'title' => trans('tvc_mall::app.mapping.product.datagrid.delete-btn'),
                'url' => route('tvc_mall.product-attribute-mapping.mass-delete'),
                'method' => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
