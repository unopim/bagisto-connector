<?php

return [
    [
        'key'   => 'sunsky_online',
        'name'  => 'Sunsky Online',
        'route' => 'sunsky_online.configuration.index',
        'sort'  => 20,
        'icon'  => 'icon-data-transfer',
    ],
    [
        'key'   => 'sunsky_online.configuration',
        'name'  => 'sunsky_online::app.components.layouts.sidebar.configuration',
        'route' => 'sunsky_online.configuration.index',
        'sort'  => 1,
        'icon'  => '',
    ],
    [
        'key'    => 'sunsky_online.attributes_mapping',
        'name'   => 'sunsky_online::app.components.layouts.sidebar.attributes-mapping',
        'route'  => 'sunsky_online.mappings.attributes.index',
        'params' => [1],
        'sort'   => 2,
        'icon'   => '',
    ],
];
