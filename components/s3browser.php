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
    
    public $region = 'us-east-1';
    
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
                 'description'       => 'the s3 bucket',
                 'type'              => 'string',
                 'required'          => true,
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
    
    public function onRun()
    {
        // get settings
        $this->activated = Settings::get('s3activated', false);
        $this->url = Settings::get('s3url', 'no-url');
        $this->region = Settings::get('s3region', 'us-east-1');
        $this->access = Settings::get('s3accesskey', 'no-access');
        $this->secret = Settings::get('s3secretkey', 'no-secret');
        
        // connect to s3 with given credentials
        $this->storage_client = new S3Client([
            'version' => 'latest',
            'region'  => $this->region,
            'endpoint' => $this->url,
            'use_path_style_endpoint' => true,
            'credentials' => [
                    'key'    => $this->access,
                    'secret' => $this->secret,
                ],
        ]);
    }
    
    public function listBuckets()
    {
        $bucketListResponse = $this->storage_client->listBuckets();
        return $bucketListResponse['Buckets'];
    }
    
    public function getObjects()
    {
        $current_prefix = '';
        
        if (is_string($this->property('prefix')))
        {
            $current_prefix = $this->property('prefix');
        }
        
        $objectsListResponse = $this->storage_client->listObjects([
            'Bucket' => $this->property('bucket'),
            'Prefix' => $current_prefix
        ]);
        
        $objects = [];
        
        foreach ($objectsListResponse['Contents'] as $object) {
            $unprefixed_key = ltrim($object['Key'], $current_prefix.'/');
            $exploded_key = explode('/', $unprefixed_key);
            
            if (count($exploded_key) == 1)
            {
                $object['ShortName'] = $exploded_key[0];
                $objects[] = $object;
            }
        }

        return $objects;
    }
    
    public function getPrefixes()
    {
        $current_prefix = '';
        
        if (is_string($this->property('prefix')))
        {
            $current_prefix = $this->property('prefix');
        }
        
        $objectsListResponse = $this->storage_client->listObjects([
            'Bucket' => $this->property('bucket'),
            'Prefix' => $current_prefix
            //'Delimiter' => '/'
        ]);
        
        $crumbs = $this->getBreadCrumbs();
        
        $prefixes = [];
        
        foreach ($objectsListResponse['Contents'] as $object) {
            $unprefixed_key = $object['Key'];
            
            foreach ($crumbs as $crumb)
            {
                $unprefixed_key = ltrim($object['Key'], $current_prefix.'/');
            }
            
            $exploded_key = explode('/', $unprefixed_key);
            
            echo print_r($exploded_key);
            
            if (count($exploded_key) == 2)
            {
                $prefixes[] = $exploded_key[0];
            }
        }
        
        return array_unique($prefixes);
    }
    
    public function getBreadCrumbs()
    {
        $current_prefix = '';
        
        if (is_string($this->property('prefix')))
        {
            $current_prefix = $this->property('prefix');
        }
        
        $crumbs = explode('/', $current_prefix);
        
        return $crumbs;
    }
    
    public function onDownload()
    {
        # lets download our file 
        $file_to_download = 'from_php_script/lullaby.txt'; # private file: 'from_php_script/lullaby-private.txt' 
        $download_as_path = __dir__.'/downloads/lullaby.txt'; 
        
        # save object to a file 
        $result = $s3->getObject([ 
        	'Bucket' => $config['s3-access']['bucket'], 
        	'Key' => $file_to_download, 
        	'SaveAs' => $download_as_path 
        ]);
    }
    
    public function doTests()
    {
        $result = '';
        
        echo print_r($result);
    }
}
