<?php

return [
    /**
     * BagistoPlugin menu
     */
    [
        'key'   => 'bagisto_plugin',
        'name'  => 'bagisto_plugin::app.components.layouts.sidebar.bagisto-plugin',
        'route' => 'admin.bagisto_plugin.credentials.index',
        'sort'  => 11,
        'icon'  => 'bagisto-icon',
    ], [
        'key'   => 'bagisto_plugin.credentials',
        'name'  => 'bagisto_plugin::app.components.layouts.sidebar.credentials',
        'route' => 'admin.bagisto_plugin.credentials.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'    => 'bagisto_plugin.attributes_mapping',
        'name'   => 'bagisto_plugin::app.components.layouts.sidebar.attributes-mapping',
        'route'  => 'admin.bagisto_plugin.mappings.attributes.index',
        'params' => [1],
        'sort'   => 2,
        'icon'   => '',
    ], [
        'key'    => 'bagisto_plugin.category_fields_mapping',
        'name'   => 'bagisto_plugin::app.components.layouts.sidebar.category-fields-mapping',
        'route'  => 'admin.bagisto_plugin.mappings.category_fields.index',
        'params' => [1],
        'sort'   => 3,
        'icon'   => '',
    ],
];
