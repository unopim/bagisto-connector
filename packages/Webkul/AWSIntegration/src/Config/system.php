<?php

return [
    [
        'key'  => 'aws',
        'name' => 'aws::app.admin.layouts.aws',
        'sort' => 5,
        'info' => 'aws::app.admin.layouts.aws'
    ], [
        'key'  => 'aws.setting',
        'name' => 'aws::app.admin.layouts.setting',
        'sort' => 2,
        'info' => 'aws::app.admin.layouts.aws'
    ], [
        'key'    => 'aws.setting.general',
        'name'   => 'aws::app.admin.layouts.general',
        'sort'   => 2,
        'info'   => 'aws::app.admin.layouts.aws',
        'fields' => [
            [
                'name'          => 'active',
                'title'         => 'aws::app.admin.system.allow-files-to-save-on-amazon',
                'type'          => 'boolean',
                'validation'    => 'required',
            ], [
                'name'          => 'access-key-id',
                'title'         => 'aws::app.admin.system.access-key-id',
                'type'          => 'password',
                'validation'    => 'required',
            ], [
                'name'          => 'secret-key',
                'title'         => 'aws::app.admin.system.secret-key',
                'type'          => 'password',
                'validation'    => 'required',
            ], [
                'name'          => 'bucket-name',
                'title'         => 'aws::app.admin.system.bucket-name',
                'type'          => 'text',
                'validation'    => 'required',
            ], [
                'name'          => 'region',
                'title'         => 'aws::app.admin.system.region',
                'validation'    => 'required',
                'type'          => 'text',
            ], [
                'name'          => 'bucket-url',
                'title'         => 'aws::app.admin.system.bucket-url',
                'validation'    => 'required',
                'type'          => 'text',
                'info'          => 'aws::app.admin.message.aws-bucket-url',
            ], [
                'name'          => 'environment-update-time',
                'title'         => 'aws::app.admin.system.environment-update-time',
                'type'          => 'text',
                'default_value' => '0'
            ]
        ]
    ]
];