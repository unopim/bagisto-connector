<?php

return [
    'admin' => [
        'layouts' => [
            'aws' => 'AWS Integration',
            'general' => 'General',
            'setting' => 'Setting'
        ],

        'acl' => [
            'aws' => 'AWS Integration'
        ],

        'system' => [
            'allow-files-to-save-on-amazon' => "Allow files to save on amazon",
            'access-key-id' => 'Access key id',
            'secret-key' => 'Secret Key',
            'bucket-name' => 'Bucket Name',
            'region' => 'Region',
            'bucket-url'    => 'Bucket URL',
            'check-bucket-availability' => 'Check bucket availability',
            'environment-update-time' => 'Environment Update Time'
        ],

        'aws-integration' => [
            'storage-configuration'=> 'Storage configuration for S3',
            'upload' => 'Upload',
            'publish-assets' => 'Publish assets',
            'synchronize' => 'Synchronize',
            'upload-flash' => 'Uploaded successfully',
            'note' => 'Note:',
            'note-msg' => 'To publish all media files (images) to the S3 bucket, click on Synchronize initially. After that, only click on Synchronize whenever new images are added or existing ones are updated.',
        ],

        'message' => [
            'configure-aws' => 'Please configure AWS',
            'synchronise' => 'Synchronised Successfully',
            'aws-bucket-url' => 'Prepare bucket URL like https://bucket-name.s3.amazonaws.com/',
        ]
    ]
];