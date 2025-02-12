<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'bagisto-plugin'          => 'Bagisto',
                'credentials'             => 'Credentials',
                'attributes-mapping'      => 'Attributes Mapping',
                'category-fields-mapping' => 'Category Fields Mapping',
            ],
        ],
    ],

    'exporters' => [
        'bagisto-plugin' => [
            'category'              => 'Bagisto Category',
            'attribute'             => 'Bagisto Attribute',
            'attribute-families'    => 'Bagisto Attribute Families',
            'product'               => 'Bagisto Product',
            'credentials'           => 'Bagisto credentials',
            'with_media'            => 'With Media',
            'channel'               => 'Channel',
            'locale'                => 'Locale',
            'code'                  => 'Filter By Code',
            'sku'                   => 'Filter By SKU',
        ],
    ],

    'bagisto-plugin' => [
        'credentials' => [
            'index' => [
                'title'      => 'Credentials',
                'create-btn' => 'Create Credential',

                'datagrid' => [
                    'id'       => 'ID',
                    'shop-url' => 'Shop Url',
                    'email'    => 'Email Address',
                    'edit'     => 'Edit',
                    'delete'   => 'Delete',
                ],

                'create' => [
                    'title'    => 'Create Credential',
                    'shop_url' => 'Shop Url',
                    'email'    => 'Email Address',
                    'password' => 'Password',
                    'save-btn' => 'Save',
                ],

                'create-success'  => 'Credential created successfully.',
                'update-success'  => 'Credential updated successfully.',
                'delete-success'  => 'Credential deleted successfully.',
            ],

            'edit' => [
                'title'                      => 'Edit Credential',
                'shop_url'                   => 'Shop Url',
                'email'                      => 'Email Address',
                'password'                   => 'Password',
                'back-btn'                   => 'Back',
                'save-btn'                   => 'Save',
                'general'                    => 'General',
                'store-config'               => 'Store Configuration',
                'category-field-mapping'     => 'Category Field Mapping',
                'category-field-filterable'  => 'Is Filterable Attributes Field Mapping',
                'bagisto-channel'            => 'Bagisto Channel',
                'bagisto-locale'             => 'Bagisto Locale',
                'unopim-channel'             => 'UnoPim Channel',
                'unopim-locale'              => 'UnoPim Locale',
            ],
        ],
        'bagisto-attributes' => [
            'attributes-name' => [
                'sku'                  => 'SKU',
                'name'                 => 'Name',
                'url_key'              => 'Url Key',
                'product_number'       => 'Product Number',
                'visible_individually' => 'Visible Individually',
                'manage_stock'         => 'Manage Stock',
                'brand'                => 'Brand',
                'price'                => 'Price',
                'special_price'        => 'Special Price',
                'cost'                 => 'Cost',
                'short_description'    => 'Short Description',
                'description'          => 'Description',
                'meta_title'           => 'Meta Title',
                'meta_keywords'        => 'Meta Keywords',
                'meta_description'     => 'Meta Description',
                'length'               => 'Length',
                'width'                => 'Width',
                'height'               => 'Height',
                'weight'               => 'Weight',
                'images'               => 'Images',
            ],
            'title' => [
                'sku'                  => 'The SKU field is a unique identifier for each product.',
                'name'                 => 'The name field is the main title of the product.',
                'url_key'              => 'The URL key is used for SEO-friendly URLs.',
                'product_number'       => 'The product number field stores an additional product identifier.',
                'visible_individually' => 'This setting determines if the product is visible individually in the catalog.',
                'manage_stock'         => 'This field specifies if stock management is enabled for the product.',
                'brand'                => 'The brand field associates a product with a brand.',
                'price'                => 'The price field represents the product’s selling price.',
                'special_price'        => 'Special price allows for discount pricing on the product.',
                'cost'                 => 'Cost represents the cost of goods for the product.',
                'short_description'    => 'The short description is a brief summary of the product.',
                'description'          => 'The description field provides detailed information about the product.',
                'meta_title'           => 'Meta title is used for SEO purposes in search engines.',
                'meta_keywords'        => 'Meta keywords help in defining search keywords for the product.',
                'meta_description'     => 'Meta description is used for SEO purposes and appears in search results.',
                'length'               => 'The length field specifies the product’s length dimensions.',
                'width'                => 'The width field specifies the product’s width dimensions.',
                'height'               => 'The height field specifies the product’s height dimensions.',
                'weight'               => 'The weight field is used for shipping calculations.',
                'images'               => 'Images field holds the product’s image gallery.',
            ],
            'success-message' => 'Attribute mapping has been saved successfully',
        ],
        'bagisto-category-fields' => [
            'field-name' => [
                'slug'             => 'Slug',
                'name'             => 'Name',
                'status'           => 'Visible In Menu',
                'description'      => 'Description',
                'meta_title'       => 'Meta Title',
                'meta_keywords'    => 'Meta Keywords',
                'meta_description' => 'Meta Description',
                'position'         => 'Position',
                'display_mode'     => 'Display Mode',
                'logo_path'        => 'Logo',
                'banner_path'      => 'Banner',
            ],
            'title' => [
                'slug'             => 'The slug field is a text-type category field.',
                'name'             => 'The name field is a text-type category field.',
                'status'           => 'The status field is a boolean-type category field.',
                'description'      => 'The description field is a textarea-type category field.',
                'meta_title'       => 'The meta_title field is a textarea-type category field.',
                'meta_keywords'    => 'The meta_keywords field is a textarea-type category field.',
                'meta_description' => 'The meta_description field is a textarea-type category field.',
                'position'         => 'The position field is a text-type category field.',
                'display_mode'     => 'The display_mode field is a select-type category field.',
                'logo_path'        => 'The logo_path field is an image-type category field.',
                'banner_path'      => 'The banner_path field is an image-type category field.',
            ],
            'success-message' => 'Category Fields mapping has been saved successfully',
        ],
        'export' => [
            'mapping' => [
                'attributes' => [
                    'title'             => 'Attribute Mappings',
                    'back-btn'          => 'Back',
                    'add'               => 'Add',
                    'save'              => 'Save',
                    'remove'            => 'Remove',
                    'bagisto-attribute' => 'Bagisto Fields',
                    'unopim-attribute'  => 'UnoPim Attributes',
                    'fixed-value'       => 'Fixed Value',
                    'flash-message'     => 'Please provide a valid attribute code and type.',
                ],

                'additional-attributes' => [
                    'title'       => 'Additional Attribute Mappings',
                    'description' => 'Write the Bagisto attribute code to add an additional attribute and map it.',
                ],

                'category-fields' => [
                    'title'                   => 'Category Fields Mappings',
                    'save'                    => 'Save',
                    'bagisto-fields'          => 'Bagisto Fields',
                    'unopim-category-fields'  => 'UnoPim Category Fields',
                    'fixed-value'             => 'Fixed Value',
                ],
            ],
        ],
        'acl' => [
            'credential' => [
                'create' => 'Create',
                'edit'   => 'Edit',
                'delete' => 'Delete',
            ],
        ],
    ],
];
