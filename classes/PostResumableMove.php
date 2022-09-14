<?php namespace mikp\s3browser\Classes;

use mikp\s3browser\Models\Settings;

use mikp\s3browser\Classes\StorageClient;
use mikp\s3browser\Classes\StorageException;

use Storage;

class PostResumableMove extends StorageClient
{
    public function postUploadOperation(\TusPhp\Events\TusEvent $event)
    {
        $fileMeta = $event->getFile()->details();

        // get location
        $path = $fileMeta['file_path'];
        $bucket = $fileMeta['metadata']['bucket'];
        $prefix = $fileMeta['metadata']['prefix'];
        $mime_type = $fileMeta['metadata']['filetype'];
        $object_name = $fileMeta['name'];
        $object_key = implode('/', [$prefix, $object_name]);

        // store file
        $this->putObject($bucket, $object_key, $path, $mime_type);

        // remove the uploaded object from local
        unlink($path);

        // remove expired files too
        app('tus-server')->handleExpiration();
    }
}
