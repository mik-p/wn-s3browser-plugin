<?php

namespace mikp\s3browser\Http\Controllers;

use Illuminate\Routing\Controller;

use mikp\s3browser\Models\Settings;

use mikp\s3browser\Classes\StorageClient;
use mikp\s3browser\Classes\StorageException;

use Response;
use Illuminate\Http\Request;
use Storage;
use ZipArchive;

class API extends Controller
{
    public $storage_client;

    public $activated = false;

    public function __construct()
    {
        $this->createS3Client();
    }

    // routes
    public function index(Request $req)
    {
        $content = '<h1>S3 Browser API</h1>';

        $base_path = $req->path();
        $content .= '<p>Base path: ' . $base_path . '/' . '</p>';

        $content .= '<p>Endpoints:</p>';

        $content .= '<ul>';
        $content .= '<li>' . $base_path . '/list/{bucket}' . '</li>';
        $content .= '<li>' . $base_path . '/object' . '</li>';
        $content .= '<li>' . $base_path . '/object/url' . '</li>';
        $content .= '<li>' . $base_path . '/delete' . '</li>';
        $content .= '<li>' . $base_path . '/download' . '</li>';
        $content .= '<li>' . $base_path . '/upload' . '</li>';
        $content .= '<li>' . $base_path . '/zip' . '</li>';
        $content .= '<li>' . $base_path . '/select' . '</li>';
        $content .= '<li>' . $base_path . '/tus/{any?}' . '</li>';
        $content .= '</ul>';

        return Response::make(
            $content,
            200
        );
    }

     // api doc file
    public function docs()
    {
        $path = plugins_path('mikp/s3browser/assets/docs/api-docs.json');
        return Response::file($path, ['Content-Type' => 'application/json']);
    }

    // list full path of objects in a bucket
    public function list(Request $req, $bucket)
    {
        $keys = $this->storage_client->listObjects($bucket);

        return response()->json(['objects' => $keys]);
    }

    // get an object as a http response body
    public function get_object(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($object_key)) {
            return Response::make('bad request missing url parameters', 400);
        }

        try {
            // get object
            $object = $this->storage_client->getObject($bucket, $object_key);

            // send file to browser as a download
            return Response::make($object['Body'])->header('Content-Type', $object['ContentType']);
        } catch (StorageException $e) {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('not found', 404);
    }

    // post an object as a http request body
    public function post_object(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->input('object_key');
        $bucket = $req->input('bucket');

        if (!isset($bucket) || !isset($object_key)) {
            return Response::make('bad request missing url parameters', 400);
        }

        // file missing from request
        if (!$req->hasFile('filename')) {
            return Response::make('bad request - missing file for upload', 400);
        }

        // check if file encdoding matches
        $file_name_in_key = explode('/', $object_key);
        $file_name_in_key = end($file_name_in_key);
        $file_name_in_key_split = explode('.', $file_name_in_key);
        if (count($file_name_in_key_split) < 2) {
            return Response::make('bad request - improper file name or extension given: "' . $file_name_in_key . '"', 400);
        }

        $file_extension = end($file_name_in_key_split);

        $warnings = [];

        if ($req->filename->extension() != $file_extension) {
            $warnings[] = 'file extension did not match named extension: "' . $req->filename->extension() . '", "' . $file_extension . '"';
            // return Response::make('bad request - file extension does not match named extension: "'.$req->filename->extension().'", "'.$file_extension.'"', 400);
        }

        // upload the file to s3
        try {
            $result = $this->storage_client->putObject(
                $bucket,
                $object_key,
                $req->filename->path(),
                $req->filename->getMimeType()
            );

            return Response::json([
                'statusCode' => $result['@metadata']['statusCode'],
                'object_key' => $object_key,
                'content_type' => $req->filename->getMimeType(),
                'warnings' => $warnings
            ]);
        } catch (StorageException $e) {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('something went wrong', 500);
    }

    // get a presigned url for the given object
    public function presigned_url(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');
        $duration_str = $req->query('duration_str', '+20 minutes');

        if (!isset($bucket) || !isset($object_key)) {
            return Response::make('bad request missing url parameters', 400);
        }

        try {
            // create the presigned URL
            $presignedUrl = $this->storage_client->createPresignedURL($bucket, $object_key, $duration_str);

            // send presigned url back
            return Response::make($presignedUrl);
        } catch (StorageException $e) {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('not found', 404);
    }

    // get an object as a http response body
    public function delete_object(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($object_key)) {
            return Response::make('bad request missing url parameters', 400);
        }

        try {
            $result = $this->storage_client->deleteObject(
                $bucket,
                $object_key
            );

            return response()->json($result, 200);
        } catch (StorageException $e) {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('not found', 404);
    }

    // download an object as a file
    public function download(Request $req)
    {
        // read request url encoded parameters
        $object_key = $req->query('object_key');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($object_key)) {
            return Response::make('bad request missing url parameters', 400);
        }

        try {
            // send object back
            $object = $this->storage_client->getObject(
                $bucket,
                $object_key
            );

            // make a file name for the download
            $exploded_key = explode('/', $object_key);
            $file_name = end($exploded_key);
            array_pop($exploded_key);
            foreach ($exploded_key as $name_part) {
                $file_name = $name_part . '-' . $file_name;
            }

            // send file to browser as a download
            $headers = ['Content-Type' => $object['ContentType']];
            return Response()->streamDownload(
                function () use ($object) {
                    echo $object['Body'];
                },
                basename($file_name),
                $headers
            );
        } catch (StorageException $e) {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('not found', 404);
    }

    // upload objects to a given location
    public function upload(Request $req)
    {
        // read request url encoded parameters
        $prefix = $req->input('prefix');
        $bucket = $req->input('bucket');

        if (!isset($bucket)) {
            return Response::make('bad request - missing url parameters', 400);
        }

        // file missing from request
        if (!$req->hasFile('filename')) {
            return Response::make('bad request - missing files for upload', 400);
        }

        if (!isset($prefix)) {
            $prefix = '';
        }

        // upload the file to s3
        $successful_uploads = [];

        foreach ($req->filename as $file_name) {
            try {
                $result = $this->storage_client->putObject(
                    $bucket,
                    $prefix . '/' . $file_name->getClientOriginalName(),
                    $file_name->path(),
                    $file_name->getMimeType()
                );

                if ($result['@metadata']['statusCode'] != 200) {
                    return Response::make('upload of file "' . $file_name->getClientOriginalName() . '" failed', 500);
                }

                $successful_uploads[] = [
                    'file' => $file_name->getClientOriginalName(),
                    'status' => $result['@metadata']['statusCode']
                ];
            } catch (StorageException $e) {
                return Response::make($e->getMessage(), 500);
            }
        }

        return Response::json($successful_uploads);
    }

    // download objects from a given location
    public function zip(Request $req)
    {
        // read request url encoded parameters
        $prefix = $req->query('prefix');
        $bucket = $req->query('bucket');

        if (!isset($bucket) || !isset($prefix)) {
            return Response::make('bad request missing url parameters', 400);
        }

        // get the list of objects in this folder
        $objects_metadata = $this->storage_client->listObjects($bucket, ltrim($prefix, $prefix[0]));

        if (count($objects_metadata) == 0) {
            // empty folder
            return Response::make('folder is empty', 200);
        }

        // do some file system stuff
        $temp_dir = 's3browser-zip-tmp';
        Storage::makeDirectory($temp_dir);
        Storage::delete(Storage::allFiles($temp_dir)); // clear the old request

        // create a zip file name
        $zip_file_name_end = date("Ymd-His") . '.zip';
        $zip_file_name_start = '';

        foreach (explode('/', ltrim($prefix, $prefix[0])) as $crumb) {
            $zip_file_name_start .= $crumb . '-';
        }

        $zip_file_name = $zip_file_name_start . $zip_file_name_end;

        // compress the files into a download-able zip
        $zip = new ZipArchive;

        if ($zip->open(Storage::path($temp_dir) . '/' . $zip_file_name, ZipArchive::CREATE) === TRUE) {
            // download all the objects
            foreach ($objects_metadata as $object) {
                // make a file name
                $exploded_key = explode('/', $object['Key']);
                $file_name = end($exploded_key);
                array_pop($exploded_key);
                foreach ($exploded_key as $name_part) {
                    $file_name = $name_part . '-' . $file_name;
                }

                // get the object
                $object = $this->storage_client->getObject($bucket, $object['Key']);

                Storage::put($temp_dir . '/' . $file_name, $object['Body']);

                // Add File in ZipArchive
                $zip->addFile(Storage::path($temp_dir) . '/' . $file_name, $file_name);
            }

            // close after done
            $zip->close();
        }

        // Create Download Response
        $zip_file_path = $temp_dir . '/' . $zip_file_name;

        if (Storage::exists($zip_file_path)) {
            return Response::streamDownload(
                function () use ($zip_file_path) {
                    $zip_contents = Storage::get($zip_file_path);
                    echo $zip_contents;
                },
                basename($zip_file_name)
            );

            // return Storage::download($zip_file_path);
        }

        return Response::make('not found', 404);
    }

    // use s3 select api
    public function select(Request $req)
    {
        // read request url encoded parameters
        $bucket = $req->query('bucket');
        $object_key = $req->query('object_key');
        $select_query = $req->query('query');

        if (!isset($bucket) || !isset($object_key) || !isset($select_query)) {
            return Response::make('bad request - missing url parameters', 400);
        }

        if (empty($select_query)) {
            return Response::make('bad request - query is empty', 400);
        }

        try {
            $response_json = $this->storage_client->call_select($bucket, $object_key, $select_query);

            if ($response_json['error_code'] == 500) {
                return Response::make('could not determine file delimiting', 500);
            }

            return Response::json($response_json);
        } catch (StorageException $e) {
            return Response::make($e->getMessage(), 500);
        }

        return Response::make('something went wrong', 500);
    }

    // use tus resumable upload api on bucket objects
    public function tus(Request $req)
    {
        if (!Settings::get('s3resumable', false))
        {
            return Response::make('the server has not been configured for this feature', 503);
        }

        if (app('tus-server') === null)
        {
            return Response::make('the server has not been configured for this feature', 503);
        }

        // serve the tus protocol
        return app('tus-server')->serve();
    }

    // helpers
    public function createS3Client()
    {
        // get settings
        $this->activated = Settings::get('s3activated', false);

        if ($this->activated) {
            // connect to s3 with given credentials
            $this->storage_client = new StorageClient();
        }
    }
}
