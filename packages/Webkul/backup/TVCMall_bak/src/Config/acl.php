<?php

return [
    [
        'key'   => 'tvc_mall',
        'name'  => 'tvc_mall::app.menu.connector',
        'route' => 'tvc_mall.index',
        'sort'  => 20,
    ], [
        'key'   => 'tvc_mall.configure',
        'name'  => 'tvc_mall::app.menu.configure',
        'route' => 'tvc_mall.configuration.index',
        'sort'  => 1,
    ], [
        'key'   => 'tvc_mall.product_mapping',
        'name'  => 'tvc_mall::app.menu.product-attribute-mapping',
        'route' => 'tvc_mall.product-attribute-mapping.index',
        'sort'  => 2,
    ], [
        'key'   => 'tvc_mall.product_mapping.create',
        'name'  => 'tvc_mall::app.menu.create',
        'route' => 'tvc_mall.product-attribute-mapping.create',
        'sort'  => 1,
    ], [
        'key'   => 'tvc_mall.product_mapping.delete',
        'name'  => 'tvc_mall::app.menu.delete',
        'route' => 'tvc_mall.product-attribute-mapping.delete',
        'sort'  => 2,
    ]
];
