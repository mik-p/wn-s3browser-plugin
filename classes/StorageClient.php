<?php

namespace mikp\s3browser\Classes;

use mikp\s3browser\Classes\StorageException;

use mikp\s3browser\Models\Settings;

use Aws\S3\Exception\S3Exception;

use PHPSQLParser\PHPSQLParser;

class StorageClient
{
    // filesystem
    public $storage_client;
    public $storage_adapter;
    public $storage_filesystem;
    // cache
    public $storage_cache;
    public $storage_cache_adapter;

    public function __construct()
    {
        $this->createFilesystem();
    }

    // helpers
    // create an adapter from settings
    public function createAdapter()
    {
        // choose an adapter from settings
        if (Settings::get('s3enable', false)) {
            // s3
            $this->storage_client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => Settings::get('s3region', 'us-east-1'),
                'endpoint' => Settings::get('s3url', 'no-url'),
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => Settings::get('s3accesskey', 'no-access'),
                    'secret' => Settings::get('s3secretkey', 'no-secret'),
                ],
            ]);

            $this->storage_adapter = new \League\Flysystem\AwsS3v3\AwsS3Adapter(
                $this->storage_client,
                Settings::get('s3bucketname', 'no-bucket')
            );

            return;
        }
        elseif (Settings::get('gcpenable', false)) {
            // gcp
            // XXX FIXME: no credentials
            $this->storage_client = new \Google\Cloud\Storage\StorageClient($clientOptions);

            $this->storage_adapter = new \Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter(
                $this->storage_client,
                $storageClient->bucket(Settings::get('gcpbucketname', 'no-bucket'))
            );

            return;
        }
        elseif (Settings::get('webdavenable', false)) {
            // webdav
            $this->storage_client = new \Sabre\DAV\Client([
                'baseUri' => Settings::get('webdavuri', 'hostname'),
                'userName' => Settings::get('webdavuser', 'username'),
                'password' => Settings::get('webdavpassword', 'password')
            ]);

            $this->storage_adapter = new \League\Flysystem\WebDAV\WebDAVAdapter($client);

            return;
        }
        elseif (Settings::get('ftpenable', false)) {
            // ftp
            $this->storage_adapter = new \League\Flysystem\Adapter\Ftp([
                'host' => Settings::get('ftphost', 'hostname'),
                'root' => Settings::get('ftproot', '/root/path/'),
                'username' => Settings::get('ftpuser', 'username'),
                'password' => Settings::get('ftppassword', 'password')
            ]);

            return;
        }
        else {
            // local
            $this->storage_adapter = new \League\Flysystem\Adapter\Local(
                storage_path('app/s3browser/')
            );

            return;
        }
    }

    // create the storage filesystem
    public function createFilesystem()
    {
        // create a cache store
        $this->storage_cache = new \League\Flysystem\Cached\Storage\Memory();

        // create an adapter
        $this->createAdapter();

        // add cache to adapter
        $this->storage_cache_adapter = new \League\Flysystem\Cached\CachedAdapter(
            $this->storage_adapter,
            $this->storage_cache
        );

        // create a filesystem
        if (Settings::get('s3usecache', false)) {
            $this->storage_filesystem = new \League\Flysystem\Filesystem($this->storage_cache_adapter);
        } else {
            $this->storage_filesystem = new \League\Flysystem\Filesystem($this->storage_adapter);
        }
    }

    // // list available buckets
    public function listBuckets()
    {
        // check if the storage system is s3 compliant
        if (!$this->storage_filesystem->getAdapter() instanceof \League\Flysystem\AwsS3v3\AwsS3Adapter) {
            throw new StorageException("current storage system doesn't support this operation");
        }

        $bucketListResponse = $this->storage_client->listBuckets();
        return $bucketListResponse['Buckets'];
    }

    // list object keys
    public function listObjects($object)
    {
        $objectsListResponse = $this->storage_filesystem->listContents(null, true);

        $object_keys = [];

        if (isset($objectsListResponse)) {
            foreach ($objectsListResponse as $object) {
                if ($object['type'] == 'file') {
                    $object_keys[] = $object['path'];
                }
            }
        }

        return $object_keys;
    }

    // list object keys in a prefix
    public function listPrefixedObjects($object, $prefix)
    {
        $objectsListResponse = $this->storage_filesystem->listContents($prefix, true);

        $object_keys = [];

        if (isset($objectsListResponse)) {
            foreach ($objectsListResponse as $object) {
                if ($object['type'] == 'file') {
                    $object_keys[] = $object['path'];
                }
            }
        }

        return $object_keys;
    }

    // list unique prefixes within a prefix (like folders)
    public function listPrefixes($bucket, $prefix)
    {
        $object_keys = $this->listPrefixedObjects($bucket, $prefix);

        $crumbs = $this->getBreadCrumbs($prefix);

        $prefixes = [];

        foreach ($object_keys as $object) {
            $unprefixed_key = $object;

            if ($prefix != '') {
                foreach ($crumbs as $crumb) {
                    $unprefixed_key = str_replace($crumb . '/', '', $unprefixed_key);
                }
            }

            $exploded_key = explode('/', $unprefixed_key);

            if (count($exploded_key) >= 2) {
                $prefixes[] = $exploded_key[0];
            }
        }

        $prefixes = array_unique($prefixes);

        return $prefixes;
    }

    // split a prefix into words
    public function getBreadCrumbs($prefix)
    {
        $crumbs = [];

        if (!empty($prefix)) {
            $crumbs = explode('/', $prefix);
        }

        return $crumbs;
    }

    // get the desired object by key
    public function getObject($bucket, $object_key)
    {
        // get content details
        $mime_type = $this->storage_filesystem->getMimetype($object_key);

        // get object
        $object = $this->storage_filesystem->read($object_key);

        if (!$object) {
            throw new StorageException("failed to retrieve object");
        }

        // send object back
        return [
            "ContentType" => $mime_type,
            "Body" => $object
        ];

        // try {
        //     $object = $this->storage_client->getObject([
        //         'Bucket' => $bucket,
        //         'Key' => $object_key
        //     ]);

        // } catch (S3Exception $e) {
        //     throw new StorageException($e->getMessage());
        // }

        // // send object back
        // return $object;
    }

    // put the desired object by key and path
    public function putObject($bucket, $object_key, $path, $mime_type)
    {
        // upload the file
        $response = $this->storage_filesystem->put($object_key, file_get_contents($path), ["mimetype" => $mime_type]);
        if (!$response) {
            throw new StorageException("failed to upload object");
        }

        return [
            "@metadata" => [
                "statusCode" => 200
            ],
            "success" => $response
        ];

        // try {
        //     $result = $this->storage_client->putObject([
        //         'Bucket' => $bucket,
        //         'Key'    => $object_key,
        //         'SourceFile' => $path,
        //         'ContentType' => $mime_type
        //     ]);
        // } catch (S3Exception $e) {
        //     throw new StorageException($e->getMessage());
        // }

        // return $result;
    }

    // delete the desired object by key
    public function deleteObject($bucket, $object_key)
    {
        // delete the file
        $response = $this->storage_filesystem->delete($object_key);
        if (!$response) {
            throw new StorageException("failed to delete object");
        }

        return [
            'DeleteMarker' => $response
        ];

        // try {
        //     $result = $this->storage_client->deleteObject([
        //         'Bucket' => $bucket,
        //         'Key' => $object_key,
        //     ]);
        // } catch (S3Exception $e) {
        //     throw new StorageException($e->getMessage());
        // }

        // return $result;
    }

    // get all objects metadata within a prefix
    public function getObjects($bucket, $prefix)
    {
        // $objectsListResponse = $this->storage_client->listObjects([
        //     'Bucket' => $bucket,
        //     'Prefix' => $prefix
        // ]);

        $objectsListResponse = $this->storage_filesystem->listContents($prefix, true);

        $objects = [];

        // if (isset($objectsListResponse['Contents'])) {

        //     foreach ($objectsListResponse['Contents'] as $object) {

        //         $unprefixed_key = $object['Key'];

        //         if ($prefix != '') {
        //             $unprefixed_key = str_replace($prefix . '/', '', $object['Key']);
        //         }

        //         $exploded_key = explode('/', $unprefixed_key);

        //         if (count($exploded_key) == 1) {
        //             $object['ShortName'] = $exploded_key[0];
        //             $objects[] = $object;
        //         }
        //     }
        // }

        if (isset($objectsListResponse)) {

            foreach ($objectsListResponse as $object) {

                if ($object['type'] == 'file') {

                    $unprefixed_key = $object['path'];

                    if ($prefix != '') {
                        $unprefixed_key = str_replace($prefix . '/', '', $object['path']);
                    }

                    $exploded_key = explode('/', $unprefixed_key);

                    if (count($exploded_key) == 1) {
                        $object['Key'] = $object["path"];
                        $object['ShortName'] = $object["basename"];
                        $object['Size'] = $object["size"];
                        $object['LastModified'] = date(DATE_ISO8601, $object["timestamp"]);
                        $objects[] = $object;
                    }
                }
            }
        }

        return $objects;
    }

    // create a presigned url
    public function createPresignedURL($bucket, $object_key, $duration_str)
    {
        // check if the storage system is s3 compliant
        if (Settings::get('s3usecache', false)) {
            $current_adapter = $this->storage_filesystem->getAdapter()->getAdapter();
        } else {
            $current_adapter = $this->storage_filesystem->getAdapter();
        }

        if (!($current_adapter instanceof \League\Flysystem\AwsS3v3\AwsS3Adapter)) {
            throw new StorageException("current storage system doesn't support this operation");
        }

        // create the presigned URL
        $cmd = $this->storage_client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $object_key
        ]);

        try {
            $request = $this->storage_client->createPresignedRequest($cmd, $duration_str);
        } catch (S3Exception $e) {
            throw new StorageException($e->getMessage());
        }

        // Get the actual presigned-url
        $presignedUrl = (string)$request->getUri();

        return $presignedUrl;
    }

    public function call_select($bucket, $object_key, $select_query)
    {
        // check if the storage system is s3 compliant
        if (Settings::get('s3usecache', false)) {
            $current_adapter = $this->storage_filesystem->getAdapter()->getAdapter();
        } else {
            $current_adapter = $this->storage_filesystem->getAdapter();
        }

        if (!($current_adapter instanceof \League\Flysystem\AwsS3v3\AwsS3Adapter)) {
            throw new StorageException("current storage system doesn't support this operation");
        }

        $parser = new PHPSQLParser();
        $parsed_query = $parser->parse($select_query);

        try {
            // retain header
            $result = $this->storage_client->selectObjectContent([
                'Bucket' => $bucket,
                'Key' => $object_key,

                'ExpressionType' => 'SQL',
                'Expression' => 'select * from s3object limit 1',

                'InputSerialization' => [
                    'CSV' => [
                        'FileHeaderInfo' => 'NONE',
                        'RecordDelimiter' => '\n',
                        'FieldDelimiter' => ',',
                    ]
                ],

                'OutputSerialization' => ['CSV' => []]
            ]);

            foreach ($result['Payload'] as $event) {
                if (isset($event['Records'])) {
                    $header_str = (string) $event['Records']['Payload'];
                } elseif (isset($event['Stats'])) {
                } elseif (isset($event['End'])) {
                }
            }

            // filter the headings that aren't needed
            $valid_headings = [];

            $base_expr_star = str_contains($parsed_query["SELECT"][0]["base_expr"], "*");

            if ($base_expr_star) {
                $valid_headings = explode(',', str_replace("\n", "", $header_str));
            } else {
                foreach (explode(',', $header_str) as $heading) {
                    if (str_contains($select_query, $heading)) {
                        $valid_headings[] = str_replace("\n", "", $heading);
                    }
                }
            }

            // perform the actual query
            $result = $this->storage_client->selectObjectContent([
                'Bucket' => $bucket,
                'Key' => $object_key,

                'ExpressionType' => 'SQL',
                'Expression' => $select_query,

                'InputSerialization' => [
                    'CSV' => [
                        'FileHeaderInfo' => 'USE',
                        'RecordDelimiter' => '\n',
                        'FieldDelimiter' => ',',
                    ]
                ],

                'OutputSerialization' => ['CSV' => []]
            ]);

            $response_json = [
                'object_key' => $object_key,
                'select_query_str' => $select_query,
                'select_query_obj' => $parser->parse($select_query),
                'header_str' => $header_str,
                'data_header' => $valid_headings
            ];

            foreach ($result['Payload'] as $event) {
                if (isset($event['Records'])) {
                    $payload = (string) $event['Records']['Payload'];

                    // payload raw
                    $response_json['records'][] = $payload;

                    // payload as 2d array
                    if (str_contains($payload, "\n")) {
                        $payload = str_replace("\r", "", $payload);
                        $records = explode("\n", $payload);
                    } elseif (str_contains($payload, "\r")) {
                        $records = explode("\r", $payload);
                    } else {
                        // return Response::make('could not determine file delimiting', 500);
                        $response_json['end'] = 'Failed';
                        $response_json['error_code'] = 500;
                        $response_json['error_message'] = 'could not determine file delimiting';
                        return $response_json;
                    }

                    // get the second dimension of the data
                    // guess that the dimensionality is the header length size
                    $second_dim = count($valid_headings);

                    foreach ($records as $record) {
                        // if the dimensions match add this entry otherwise throw it out it causes problems
                        $row_data = explode(',', $record);
                        if (count($row_data) == $second_dim) {
                            $response_json['data'][] = $row_data;
                        }
                    }
                } elseif (isset($event['Stats'])) {
                    $response_json['stats'] = 'Processed ' . $event['Stats']['Details']['BytesProcessed'] . ' bytes';
                } elseif (isset($event['End'])) {
                    $response_json['end'] = 'Complete';
                }
            }

        } catch (S3Exception $e) {
            throw new StorageException($e->getMessage());
        }

        return $response_json;
    }
}
