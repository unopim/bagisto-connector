<?php

namespace Webkul\BagistoPlugin\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CredentialDataGrid extends DataGrid
{
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('wk_bagisto_credential')
            ->select(
                'id',
                'shop_url',
                'email'
            );

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'shop_url',
            'label'      => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.datagrid.shop-url'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'email',
            'label'      => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.datagrid.email'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('bagisto_plugin.credentials.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.bagisto_plugin.credentials.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('bagisto_plugin.credentials.destroy')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('bagisto_plugin::app.bagisto-plugin.credentials.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.bagisto_plugin.credentials.destroy', $row->id);
                },
            ]);
        }
    }
}
