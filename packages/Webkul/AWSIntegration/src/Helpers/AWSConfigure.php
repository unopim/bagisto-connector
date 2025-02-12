<?php

namespace Webkul\AWSIntegration\Helpers;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class AWSConfigure
{
    /**
     * retrieve AWS details from admin configuration
     */
    private function getAWSConfigData()
    {
        return [
            'status' => core()->getConfigData('aws.setting.general.active'),
            'accessKey' => core()->getConfigData('aws.setting.general.access-key-id'),
            'secretKey' => core()->getConfigData('aws.setting.general.secret-key'),
            'region' => core()->getConfigData('aws.setting.general.region'),
            'bucketName' => core()->getConfigData('aws.setting.general.bucket-name'),
            'bucketURL'  => core()->getConfigData('aws.setting.general.bucket-url'),
            'expireHeader' => core()->getConfigData('aws.setting.general.environment-update-time') ?? '86400'
        ];
    }

    /**
     * set filesystem to S3
     */
    public function uploadToS3()
    {
        $awsData = $this->getAWSConfigData();

        if ($awsData['status'] == 1) {
            \Config::set('filesystems.default', "s3");

            \Config::set('filesystems.disks.s3', [
                "driver"     => "s3",
                "key"        => $awsData['accessKey'],
                "secret"     => $awsData['secretKey'],
                "region"     => $awsData['region'],
                "bucket"     => $awsData['bucketName'],
                "url"        => $awsData['bucketURL'],
                "visibility" => 'public',
                "options"    => ['CacheControl' => 'max-age='.$awsData['expireHeader'].', public']
            ]);
        }
    }

    /**
     * publish static content to s3 bucket like images, css, js
     *
     */
    private function publishAssetsToS3()
    {
        $storageDirectory = public_path() . "/storage/";

        if (is_dir($storageDirectory)) {

            $storageFiles = \File::allFiles($storageDirectory);

            // storage folder upload
            foreach($storageFiles as $object)
            {
                $location = $object->getRelativePathName();
                $s3ProductImage = \Storage::disk('s3')->put($location, file_get_contents($object));
            }
        }
    }


    /**
     * synchronise the assets (css, js) upon compilation
     *
     */
    public function syncAssets()
    {
        if (core()->getConfigData('aws.setting.general.active') == 1) {
            $files = shell_exec('aws s3 ls s3://'.core()->getConfigData('aws.setting.general.bucket-name').'/vendor');

            if (is_null($files)) {
                $this->publishAssetsToS3();
            } else {
                $storageDirectory = public_path() . '/storage';

                $destination = 's3://'.core()->getConfigData('aws.setting.general.bucket-name');

                if (is_dir($storageDirectory)) {
                    shell_exec('aws s3 sync ' . $storageDirectory . ' ' . $destination);
                }
            }

            session()->flash('success', trans('aws::app.admin.message.synchronise'));

            return back();
        } else {
            session()->flash('warning', trans('aws::app.admin.message.configure-aws'));

            return redirect()->back();
        }

    }
}