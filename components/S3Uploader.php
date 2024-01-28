<?php

namespace mikp\s3browser\Components;

use mikp\s3browser\Classes\S3Component;

use mikp\s3browser\Models\Settings;

class S3Uploader extends S3Component
{
    public static $uploader_css_loaded = 0;
    public static $uploader_src_loaded = 0;

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

    // only load uploader css once for each page
    public static function renderUploaderSrcCss()
    {
        if (!S3Uploader::$uploader_css_loaded)
        {
            S3Uploader::$uploader_css_loaded += 1;
            return true;
        }

        return false;
    }

    // only load uploader src once for each page
    public static function renderUploaderSrcLoader()
    {
        if (!S3Uploader::$uploader_src_loaded)
        {
            S3Uploader::$uploader_src_loaded += 1;
            return true;
        }

        return false;
    }
}
