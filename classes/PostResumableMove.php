<?php namespace mikp\s3browser\Classes;

use mikp\s3browser\Models\Settings;

use mikp\s3browser\Classes\StorageClient;
use mikp\s3browser\Classes\StorageException;

use Storage;

/**
 * PostResumableMove
 *
 * Move uploaded file to s3 after post
 */
class PostResumableMove extends StorageClient
{
    public function postUploadOperation(\TusPhp\Events\TusEvent $event)
    {
        $fileMeta = $event->getFile()->details();

        // get location
        $path = $fileMeta['file_path'];
        $bucket = $fileMeta['metadata']['bucket'];
        $prefix = rtrim($fileMeta['metadata']['prefix'], '/');
        $mime_type = $fileMeta['metadata']['filetype'];
        $object_name = $fileMeta['name'];
        $object_key = implode('/', [$prefix, $object_name]);

        // trace_log('uploading after post');
        // trace_log([$path,
        //     $bucket,
        //     $prefix,
        //     $mime_type,
        //     $object_name,
        //     $object_key
        // ]);

        // store file
        $result = $this->putObject($bucket, $object_key, $path, $mime_type);

        // trace_log($result);

        // TODO:
        // remove the uploaded object from local
        // unlink($path);

        // remove expired files too
        $expired = app('tus-server')->handleExpiration();

        // trace_log($expired);
    }
}
