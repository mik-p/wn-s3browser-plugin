<?php namespace mikp\s3browser\Components;

use Cms\Classes\ComponentBase;

// use Event;

class S3Uploader extends ComponentBase
{
    public $api_basepath = '/api/v1/s3browser';

    public function componentDetails()
    {
        return [
            'name'        => 's3browser Uploader Component',
            'description' => 's3 object uploader'
        ];
    }

    public function defineProperties()
    {
        return [
            'bucket' => [
                 'title'             => 'bucket',
                 'description'       => 'the s3 bucket',
                 'type'              => 'string',
                 'required'          => true,
                 'validationPattern' => '',
                 'validationMessage' => 'the bucket property can contain only a valid bucket name'
            ]
        ];
    }
}
