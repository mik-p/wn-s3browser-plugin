<?php

namespace mikp\s3browser\Classes;

use Cms\Classes\ComponentBase;

use mikp\s3browser\Models\Settings;

use mikp\s3browser\Classes\StorageClient;

use Event;

abstract class S3Component extends ComponentBase
{
    public $storage_client;

    public $activated = false;

    public $api_basepath = '/api/v1/s3browser';

    public $bucket = 'no-bucket';

    public static function pretty_convert_bytes($size)
    {
        $unit=array('B','KB','MB','GB','TB','PB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
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
        if ($this->property('bucket', '') !== '')
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
