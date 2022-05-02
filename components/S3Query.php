<?php namespace mikp\s3browser\Components;

use Cms\Classes\ComponentBase;

use mikp\s3browser\Models\Settings;

class S3Query extends ComponentBase
{
    public $api_basepath = '/api/v1/s3browser';

    public $bucket = 'no-bucket';

    public function componentDetails()
    {
        return [
            'name'        => 'S3 Select Query',
            'description' => 's3 select query component'
        ];
    }

    public function defineProperties()
    {
        return [
            'bucket' => [
                'title'             => 'bucket',
                'description'       => 'the s3 bucket to view overriding the component settings',
                'default'           => '',
                'type'              => 'string',
                // 'required'          => true, // no longer required
                'validationPattern' => '',
                'validationMessage' => 'the bucket property can contain only a valid bucket name'
            ]
        ];
    }

    public function init()
    {
        // get bucket from setting
        $this->bucket = Settings::get('s3bucketname', 'no-bucket');
        // override if property present
        if($this->property('bucket') !== '')
        {
            $this->bucket = $this->property('bucket');
        }
    }
}
