<?php

return [
    'sunskyCategories' => [
        'title'     => 'sunsky_online::app.importers.categories.title',
        'importer'  => 'Webkul\SunskyOnline\Helpers\Importers\Category\Importer',
        'filters'   => [
            'fields'  => [
                [
                    'name'       => 'gmtModifiedStart',
                    'title'      => 'Modified Since (GMT):',
                    'required'   => false,
                    'type'       => 'datetime',
                ],
                [
                    'name'       => 'locale',
                    'title'      => 'Locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'sunsky_online.locales.index',
                ],
            ],
        ],
    ],
    'sunskyProducts' => [
        'title'     => 'sunsky_online::app.importers.products.title',
        'importer'  => 'Webkul\SunskyOnline\Helpers\Importers\Product\Importer',
        'filters'   => [
            'fields'  => [
                [
                    'name'       => 'locale',
                    'title'      => 'Locale',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'sunsky_online.locales.index',
                ],
                [
                    'name'       => 'identifiers',
                    'title'      => 'Identifiers (Comma Separated)',
                    'type'       => 'text',
                ],
                [
                    'name'       => 'in_detailed',
                    'title'      => 'With All Values like (Brands, Params) slow',
                    'type'       => 'boolean',
                ],
                [
                    'name'       => 'is_skip_already_exist',
                    'title'      => 'Skip if already Exist',
                    'type'       => 'boolean',
                ],
                [
                    'name'       => 'gmtModifiedStart',
                    'title'      => 'Modified Since (GMT):',
                    'required'   => false,
                    'type'       => 'datetime',
                ],
                [
                    'name'       => 'leadTimeLevel',
                    'title'      => 'Lead Time Level',
                    'type'       => 'datetime',
                ],
                [
                    'name'       => 'from_page_no',
                    'title'      => 'From Page No',
                    'type'       => 'number',
                ],
                [
                    'name'       => 'end_page_no',
                    'title'      => 'To Page No',
                    'type'       => 'number',
                ],
                [
                    'name'       => 'categoryId',
                    'title'      => 'Category ID',
                    'type'       => 'number',
                ],
                [
                    'name'       => 'status',
                    'title'      => 'Status',
                    'type'       => 'text',
                ],
                [
                    'name'       => 'brandName',
                    'title'      => 'Brand Name',
                    'type'       => 'text',
                ],
                [
                    'name'       => 'pageSize',
                    'title'      => 'Page Size (100)',
                    'type'       => 'text',
                ],
            ],
        ],
    ],
];
