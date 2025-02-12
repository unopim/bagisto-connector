<?php

return [
    'TVCMallCategories' => [
        'title' => 'tvc_mall::app.importers.category.type',
        'importer' => 'Webkul\TVCMall\Helpers\Importers\Category\Importer',
        'filters' => [
            'fields' => [
                [
                    'name' => 'ParentCode',
                    'title' => 'Parent Code (Keep blank for all categories)',
                    'required' => false,
                    'type' => 'text',
                ],
            ],
        ],
    ],
    'TVCMallProducts' => [
        'title' => 'tvc_mall::app.importers.product.type',
        'importer' => 'Webkul\TVCMall\Helpers\Importers\Product\Importer',
        'filters' => [
            'fields' => [
                [
                    'name' => 'CategoryCode',
                    'title' => 'Category Code',
                    'required' => false,
                    'type' => 'text',
                ], [
                    'name' => 'Status',
                    'title' => 'Status',
                    'required' => false,
                    'type' => 'text',
                ], [
                    'name' => 'Manufacturer',
                    'title' => 'Manufacturer',
                    'required' => false,
                    'type' => 'text',
                ], [
                    'name' => 'PageSize',
                    'title' => 'Page Size',
                    'required' => false,
                    'type' => 'text',
                ], [
                    'name' => 'TotalPages',
                    'title' => 'Total Pages',
                    'required' => false,
                    'type' => 'text',
                ], [
                    'name' => 'lastProductId',
                    'title' => 'Last Product ID',
                    'required' => false,
                    'type' => 'text',
                ], [
                    'name' => 'hasEanCode',
                    'title' => 'Has EAN Code?',
                    'required' => false,
                    'type' => 'text',
                ],
            ],
        ],
    ],
];
