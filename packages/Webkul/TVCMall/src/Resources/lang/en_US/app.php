<?php

return [
    'menu' => [
        'connector' => 'TVC Mall',
        'configure' => 'Configure',
        'product-attribute-mapping' => 'Product Attribute Mapping',
        'create' => 'Create',
        'delete' => 'Delete'
    ],
    'configuration' => [
        'edit' => [
            'title' => 'Configurations',
            'save' => 'Save',
            'url' => 'API URL',
            'email' => 'Email',
            'password' => 'Password',
        ],
        'alert' => [
            'success' => 'Configuration successfully saved!',
        ],
    ],
    'mapping' => [
        'product' => [
            'title' => 'Product Attribute Mappings',
            'datagrid' => [
                'save-btn' => 'Save',
                'id' => 'ID',
                'unopim_code' => 'PIM Code',
                'tvc_mall_code' => 'TVC Mall Code',
                'create-btn' => 'Create',
                'create-title' => 'Create Mapping',
                'unopim-code' => 'PIM Code',
                'tvc-mall-code' => 'TVC Mall Code',
                'create-success' => 'Mapping successfully created!',
                'unique-attribute' => 'This attribute is already mapped!',
                'delete-success' => 'Mapping successfully deleted',
                'delete-failed' => 'Delete Operation Failed',
                'delete-btn' => 'Delete'
            ],
            'create' => [
                'title' => 'Create Mapping',
                'save' => 'Save',
            ],
            'alert' => [
                'success' => 'Mappings successfully saved!',
            ],
        ],
    ],
    'importers' => [
        'category' => [
            'type' => 'TVCMall Categories',
            'filters' => [
                'parent-code' => 'Parent Code',
            ],
        ],
        'product' => [
            'type' => 'TVCMall Products',
            'filters' => [
            ],
        ],
    ],
];
