<?php

namespace mikp\s3browser\Components;

use Cms\Classes\ComponentBase;

use mikp\s3browser\Models\Settings;

use mikp\s3browser\Classes\StorageClient;

use Event;

class S3Browser extends ComponentBase
{
    public $storage_client;

    public $activated = false;

    public $api_basepath = '/api/v1/s3browser';

    public $bucket = 'no-bucket';

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

    public function onRun()
    {
        $this->createS3Client();
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

        // create client
        $this->createS3Client();
    }

    public function createS3Client()
    {
        // get settings
        $this->activated = Settings::get('s3activated', false);

        if ($this->activated) {
            // connect to s3 with given credentials
            $this->storage_client = new StorageClient();
        }
    }

    public function listBuckets()
    {
        return $this->storage_client->listBuckets();
    }

    public function getObjects()
    {
        $current_prefix = '';

        if (is_string($this->property('prefix'))) {
            $current_prefix = $this->property('prefix');
        }

        $objects = $this->storage_client->getObjects($this->bucket, $current_prefix);

        // allow events to modify the objects, this allows auth to be added
        // $loc_event_resp = $this->fireEvent('getObjects', [$objects]);
        $glob_event_resp = Event::fire('mikp.s3browser.getObjects', [$this, $objects]);

        if (!empty($glob_event_resp)) {
            $objects = $glob_event_resp[0];
        }

        return $objects;
    }

    public function getPrefixes()
    {
        $current_prefix = '';

        if (is_string($this->property('prefix'))) {
            $current_prefix = $this->property('prefix');
        }

        $prefixes = $this->storage_client->listPrefixes($this->bucket, $current_prefix);
        $crumbs = $this->getBreadCrumbs();

        // allow events to modify the prefixes, this allows auth to be added
        // $loc_event_resp = $this->fireEvent('getPrefixes', [$prefixes]);
        $glob_event_resp = Event::fire('mikp.s3browser.getPrefixes', [$this, $prefixes, $crumbs]);

        if (!empty($glob_event_resp)) {
            $prefixes = $glob_event_resp[0];
        }

        return $prefixes;
    }

    public function getBreadCrumbs()
    {
        $current_prefix = '';

        if (is_string($this->property('prefix'))) {
            $current_prefix = $this->property('prefix');
        }

        return $this->storage_client->getBreadCrumbs($current_prefix);
    }

    public function onFileDetails()
    {
        $this->page['s3_file_details'] = [
            'active' => true,
            'name' => post('short_name'),
            'path' => post('file_name'),
            'api_download' => $this->api_basepath . '/download?bucket=' . $this->bucket . '&object_key=' . urlencode(post('file_name')),
            'api_get' => $this->api_basepath . '/object?bucket=' . $this->bucket . '&object_key=' . urlencode(post('file_name'))
        ];
    }
}
