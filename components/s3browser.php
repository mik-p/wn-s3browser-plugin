<?php namespace mikp\s3browser\Components;

use Cms\Classes\ComponentBase;

use mikp\s3browser\Models\Settings;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class S3Browser extends ComponentBase
{
    public $storage_client;
    
    public $activated = false;
    
    public $url = '';
    
    public $access = '';
    
    public $secret = '';
    
    public $subPaths = [];
    
    public function componentDetails()
    {
        return [
            'name'        => 's3browser Component',
            'description' => 's3 object browser'
        ];
    }

    public function defineProperties()
    {
        return [
            'subPath' => [
                 'title'             => 'Sub Path',
                 'description'       => 'The optional S3 bucket sub-path',
                 'default'           => '/',
                 'type'              => 'string',
                 'validationPattern' => '^[0-9]+$',
                 'validationMessage' => 'The Sub Path property can contain only a uri'
            ]
        ];
    }
    
    public function onRun()
    {
        // get settings
        $this->activated = Settings::get('s3activated', false);
        $this->url = Settings::get('s3url', 'no-url');
        $this->access = Settings::get('s3accesskey', 'no-access');
        $this->secret = Settings::get('s3secretkey', 'no-secret');
        
        // connect to s3 with given credentials
        $this->storage_client = new S3Client([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'endpoint' => $this->url,
            'use_path_style_endpoint' => true,
            'credentials' => [
                    'key'    => $this->access,
                    'secret' => $this->secret,
                ],
        ]);
        
        $buckets = $this->storage_client->listBuckets();
        foreach ($buckets['Buckets'] as $bucket) {
             $this->subPaths[] = $bucket['Name'];
        }
    }
}
