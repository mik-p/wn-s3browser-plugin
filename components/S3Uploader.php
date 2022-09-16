<?php

namespace mikp\s3browser\Components;

use mikp\s3browser\Classes\S3Component;

use mikp\s3browser\Models\Settings;

class S3Uploader extends S3Component
{
    public function componentDetails()
    {
        return [
            'name'        => 'S3 Uploader',
            'description' => 's3 object uploader'
        ];
    }

    public function defineProperties()
    {
        return [
            'tagline' => [
                'title'             => 'Tag Line',
                'description'       => 'an optional tag line to print',
                'default'           => '',
                'type'              => 'string',
                // 'required'          => true, // no longer required
                'validationPattern' => '',
                'validationMessage' => 'an optional tag line to print'
            ],
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

    public function useResumable()
    {
        return Settings::get('s3resumable', false);
    }
}
