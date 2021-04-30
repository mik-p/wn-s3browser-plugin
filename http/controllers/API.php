<?php namespace mikp\s3browser\Http\Controllers;

use Illuminate\Routing\Controller;

use mikp\s3browser\Models\Settings;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

use Auth;
use App;
use Response;
use Illuminate\Http\Request;
use Storage;
use ZipArchive;

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
    public function index(Request $req)
    {
        $content = '<h1>S3 Browser API</h1>';

        $base_path = $req->path();
        $content .= '<p>Base path: '.$base_path.'/'.'</p>';

        $content .= '<p>Endpoints:</p>';

        $content .= '<ul>';
        $content .= '<li>'.$base_path.'/list/{bucket}'.'</li>';
        $content .= '<li>'.$base_path.'/object'.'</li>';
        $content .= '<li>'.$base_path.'/download'.'</li>';
        $content .= '<li>'.$base_path.'/upload'.'</li>';
        $content .= '</ul>';

        return Response::make(
            $content,
            200
        );
    }

    public function list(Request $req, $bucket)
    {
        $keys = $this->listObjects($bucket);

        return response()->json(['objects' => $keys]);
    }

    public function get_object(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($object_key))
        {
            return Response::make('bad request missing url parameters', 400);
        }

        try
        {
            // send object back
            $object = $this->storage_client->getObject([
                'Bucket' => $bucket,
                'Key' => $object_key
            ]);

            // send file to browser as a download
            return Response::make($object['Body'])->header('Content-Type', $object['ContentType']);
        }
        catch (S3Exception $e)
        {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('hi', 200);
    }

    public function post_object(Request $req)
    {
        return Response::make('hi', 200);
    }

    public function download(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($object_key))
        {
            return Response::make('bad request missing url parameters', 400);
        }

        try
        {
            // send object back
            $object = $this->storage_client->getObject([
                'Bucket' => $bucket,
                'Key' => $object_key
            ]);

            // make a file name for the download
            $exploded_key = explode('/', $object_key);
            $file_name = end($exploded_key);
            array_pop($exploded_key);
            foreach ($exploded_key as $name_part)
            {
                $file_name = $name_part.'-'.$file_name;
            }

            // send file to browser as a download
            $headers = ['Content-Type' => $object['ContentType']];
            return Response()->streamDownload(
                function () use ($object) { echo $object['Body']; },
                basename($file_name),
                $headers
            );
        }
        catch (S3Exception $e)
        {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('not found', 404);
    }

    public function upload(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($object_key))
        {
            return Response::make('bad request missing url parameters', 400);
        }

        if(isset($_FILES['image'])){
            // $file_name = $_FILES['image']['name'];
            $temp_file_location = $_FILES['image']['tmp_name'];

            $result = $this->storage_client->putObject([
                'Bucket' => $bucket,
                'Key'    => $object_key,
                'SourceFile' => $temp_file_location
            ]);

            var_dump($result);
        }
    }

    public function zip(Request $req)
    {
        // read request url encoded parameters
        $prefix = $req->query('prefix');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($prefix))
        {
            return Response::make('bad request missing url parameters', 400);
        }

        // get the list of objects in this folder
        $objectsListResponse = $this->storage_client->listObjects([
            'Bucket' => $bucket,
            'Prefix' => ltrim($prefix, $prefix[0])
        ]);

        if (isset($objectsListResponse['Contents']))
        {
            if(count($objectsListResponse['Contents']) == 0)
            {
                // empty folder
                return Response::make('folder is empty', 200);
            }

            // do some file system stuff
            $temp_dir = 's3browser-zip-tmp';
            Storage::makeDirectory($temp_dir);
            Storage::delete(Storage::allFiles($temp_dir)); // clear the old request

            // create a zip file name
            $zip_file_name_end = date("Ymd-His").'.zip';
            $zip_file_name_start = '';

            foreach (explode('/', ltrim($prefix, $prefix[0])) as $crumb)
            {
                $zip_file_name_start .= $crumb.'-';
            }

            $zip_file_name = $zip_file_name_start.$zip_file_name_end;

            // compress the files into a download-able zip
            $zip = new ZipArchive;

            if ($zip->open(Storage::path($temp_dir).'/'.$zip_file_name, ZipArchive::CREATE) === TRUE)
            {
                // download all the objects
                foreach($objectsListResponse['Contents'] as $object)
                {
                    // make a file name
                    $exploded_key = explode('/', $object['Key']);
                    $file_name = end($exploded_key);
                    array_pop($exploded_key);
                    foreach ($exploded_key as $name_part)
                    {
                        $file_name = $name_part.'-'.$file_name;
                    }

                    // get the object
                    $object = $this->storage_client->getObject([
                        'Bucket' => $bucket,
                        'Key' => $object['Key']
                    ]);

                    Storage::put($temp_dir.'/'.$file_name, $object['Body']);

                    // Add File in ZipArchive
                    $zip->addFile(Storage::path($temp_dir).'/'.$file_name, $file_name);
                }

                // close after done
                $zip->close();
            }

            // Create Download Response
            $zip_file_path = $temp_dir.'/'.$zip_file_name;

            if(Storage::exists($zip_file_path))
            {
                return Response::streamDownload(
                    function () use ($zip_file_path) {
                        $zip_contents = Storage::get($zip_file_path);
                        echo $zip_contents;
                    },
                    basename($zip_file_name)
                );

                // return Storage::download($zip_file_path);
            }
        }

        return Response::make('not found', 404);
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

    public function listObjects($bucket)
    {
        $objectsListResponse = $this->storage_client->listObjects([
            'Bucket' => $bucket
        ]);

        $object_keys = [];

        if (isset($objectsListResponse['Contents']))
        {
            foreach ($objectsListResponse['Contents'] as $object)
            {
                $object_keys[] = $object['Key'];
            }
        }

        return $object_keys;
    }
}
