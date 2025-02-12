<?php

return [
    [
        'key'   => 'bagisto_plugin',
        'name'  => 'bagisto_plugin::app.components.layouts.sidebar.bagisto-plugin',
        'route' => 'admin.bagisto_plugin.credentials.index',
        'sort'  => 11,
    ], [
        'key'   => 'bagisto_plugin.credentials',
        'name'  => 'bagisto_plugin::app.components.layouts.sidebar.credentials',
        'route' => 'admin.bagisto_plugin.credentials.index',
        'sort'  => 1,
    ], [
        'key'   => 'bagisto_plugin.credentials.store',
        'name'  => 'bagisto_plugin::app.bagisto-plugin.acl.credential.create',
        'route' => 'admin.bagisto_plugin.credentials.store',
        'sort'  => 1,
    ], [
        'key'   => 'bagisto_plugin.credentials.edit',
        'name'  => 'bagisto_plugin::app.bagisto-plugin.acl.credential.edit',
        'route' => 'admin.bagisto_plugin.credentials.edit',
        'sort'  => 2,
    ], [
        'key'   => 'bagisto_plugin.credentials.destroy',
        'name'  => 'bagisto_plugin::app.bagisto-plugin.acl.credential.delete',
        'route' => 'admin.bagisto_plugin.credentials.destroy',
        'sort'  => 3,
    ], [
        'key'   => 'bagisto_plugin.attributes_mapping',
        'name'  => 'bagisto_plugin::app.components.layouts.sidebar.attributes-mapping',
        'route' => 'admin.bagisto_plugin.mappings.attributes.index',
        'sort'  => 2,
    ], [
        'key'   => 'bagisto_plugin.category_fields_mapping',
        'name'  => 'bagisto_plugin::app.components.layouts.sidebar.category-fields-mapping',
        'route' => 'admin.bagisto_plugin.mappings.category_fields.index',
        'sort'  => 3,
    ],
];
