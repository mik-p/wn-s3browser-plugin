<?php namespace mikp\s3browser\Components;

use Cms\Classes\ComponentBase;

class S3Query extends ComponentBase
{
    public $api_basepath = '/api/v1/s3browser';

    public function componentDetails()
    {
        return [
            'name'        => 's3browser Select Query Component',
            'description' => 's3 select query component'
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
