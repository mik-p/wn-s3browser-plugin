<?php namespace mikp\s3browser\Http\Controllers;

use Illuminate\Routing\Controller;

use mikp\s3browser\Models\Settings;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

use Auth;
use App;
use Response;

class API extends Controller
{
    public $storage_client;

    public $activated = false;

    public $url = '';

    public $region = 'us-east-1';

    public $access = '';

    public $secret = '';

    public function __construct()
    {
        $this->createS3Client();
    }

    // routes
    public function index()
    {
        return Response::make(
            '<h1>S3 Browser API</h1>',
            200
        );
    }

    public function download($file)
    {
        return Response::make('not found', 404);
        // App::abort(404, 'could not find resource');
    }

    public function upload($request)
    {
        # code...
    }

    // helpers
    public function createS3Client ()
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

            $unprefixed_key = $object['Key'];

            if ($current_prefix != '')
            {
                $unprefixed_key = str_replace($current_prefix.'/', '', $object['Key']);
            }

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

            if ($current_prefix != '')
            {
                foreach ($crumbs as $crumb)
                {
                    $unprefixed_key = str_replace($crumb.'/', '', $unprefixed_key);
                }
            }

            $exploded_key = explode('/', $unprefixed_key);

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
}
