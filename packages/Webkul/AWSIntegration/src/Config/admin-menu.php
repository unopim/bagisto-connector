<?php

return [
    [
        'key'   => 'aws',
        'name'  => 'aws::app.admin.layouts.aws',
        'route' => 'admin.aws.index',
        'sort'  => 10,
        'icon'  => 'icon-magic-ai',
    ], [
        'key'   => 'aws.general',
        'name'  => 'aws::app.admin.layouts.general',
        'route' => 'admin.aws.index',
        'sort'  => 1,
    ], [
        'key'    => 'aws.aws_settings',
        'name'   => 'aws::app.admin.layouts.aws',
        'route'  => 'admin.configuration.edit',
        'params' => ['aws', 'setting'],
        'sort'   => 3,
        'icon'   => '',
    ],
];