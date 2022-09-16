<?php

namespace mikp\s3browser\Components;

use mikp\s3browser\Classes\S3Component;

use mikp\s3browser\Models\Settings;

class S3Browser extends S3Component
{
    public function componentDetails()
    {
        return [
            'name'        => 'S3 Browser',
            'description' => 's3 object browser'
        ];
    }

    public function defineProperties()
    {
        return [
            'baseuri' => [
                'title'             => 'base uri',
                'description'       => 'the browser component base uri',
                'type'              => 'string',
                'required'          => true,
                'default'           => '/',
                'validationPattern' => '',
                'validationMessage' => 'the base uri property can contain only a uri'
            ],
            'bucket' => [
                'title'             => 'bucket',
                'description'       => 'the s3 bucket to view overriding the component settings',
                'default'           => '',
                'type'              => 'string',
                // 'required'          => true, // no longer required
                'validationPattern' => '',
                'validationMessage' => 'the bucket property can contain only a valid bucket name'
            ],
            'prefix' => [
                'title'             => 'path prefix',
                'description'       => 'the optional S3 bucket path prefix',
                'default'           => '',
                'type'              => 'string',
                'validationPattern' => '',
                'validationMessage' => 'the prefix property can contain only a uri'
            ]
        ];
    }
}
