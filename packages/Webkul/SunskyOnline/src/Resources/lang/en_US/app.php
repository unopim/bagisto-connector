<?php

return [
    'configuration' => [
        'edit' => [
            'title'                      => 'Configuration',
            'save'                       => 'Save configuration',
            'url'                        => 'API URL',
            'key'                        => 'Key',
            'secret'                     => 'Secret',
            'locales-mapping'            => 'Locales Mapping',
            'unopim-locale'              => 'Unopim Locale',
            'select-unopim-locale'       => 'Select Unopim Locale',
            'sunsky-language'            => 'Sunsky Language',
            'select-sunsky-language'     => 'Select Sunsky Language',
        ],
    ],
    'importers' => [
        'categories' => [
            'title' => 'Sunsky Categories Import',
        ],
        'products' => [
            'title'  => 'Sunsky Products Import',
            'images' => [
                'title' => 'Sunsky Products Images Import',
            ],
        ],
    ],
    'components' => [
        'layouts' => [
            'sidebar' => [
                'configuration'         => 'Configuration',
                'attributes-mapping'    => 'Attributes Mapping',
            ],
        ],
    ],
    'mappings' => [
        'attribute' => [
            'title'                     => 'Attribute Mapping',
            'save'                      => 'Save',
            'add'                       => 'Add',
            'sunsky-attribute'          => 'Sunsky Attribute',
            'unopim-attribute'          => 'Unopim Attribute',
            'fixed-value'               => 'Fallback / Fixed Value',
            'attribute_or_type_missing' => 'The attribute or its type is missing.',
            'sunsky'                    => [
                'gmtModified'       => 'Last Modified Date',
                'packWidth'         => 'Package Width',
                'groupItemNo'       => 'Group Item Number',
                'oem'               => 'Original Equipment Manufacturer',
                'unitHeight'        => 'Unit Height',
                'modelLabel'        => 'Model Label',
                'gmtListed'         => 'Listed Date',
                'packQty'           => 'Package Quantity',
                'warehouse'         => 'Warehouse Location',
                'brandName'         => 'Brand Name',
                'id'                => 'Identifier',
                'withLogo'          => 'Includes Logo',
                'stock'             => 'Stock Availability',
                'unitLength'        => 'Unit Length',
                'description'       => 'Product Description',
                'name'              => 'Product Name',
                'unitWidth'         => 'Unit Width',
                'picCount'          => 'Picture Count',
                'goodsType'         => 'Goods Type',
                'itemNo'            => 'Item Number',
                'status'            => 'Product Status',
                'categoryId'        => 'Category Identifier',
                'priceList'         => 'Price List',
                'barcode'           => 'Barcode',
                'modelList'         => 'Model List',
                'moq'               => 'Minimum Order Quantity',
                'clearance'         => 'Clearance Status',
                'leadTimeLevel'     => 'Lead Time Level',
                'packWeight'        => 'Package Weight',
                'price'             => 'Product Price',
                'packLength'        => 'Package Length',
                'baseImgCount'      => 'Base Image Count',
                'unitWeight'        => 'Unit Weight',
                'packHeight'        => 'Package Height',
                'containsBattery'   => 'Contains Battery',
            ],
            'additional-attributes' => [
                'title'         => 'Additional Attributes',
                'description'   => 'You can add additional attributes in this section as needed.',
            ],

        ],
    ],
];
