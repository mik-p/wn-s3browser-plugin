<?php

namespace mikp\s3browser\Classes;

use mikp\s3browser\Classes\StorageException;

use mikp\s3browser\Models\Settings;

use Aws\S3\Exception\S3Exception;

use PHPSQLParser\PHPSQLParser;

use Storage;

class StorageClient
{
    public function __construct()
    {
    }

    // helpers
    // // list available buckets
    public function listBuckets()
    {
        // check if the storage system is s3 compliant
        if (Settings::get('s3usecache', false)) {
            $current_adapter = Storage::disk('s3browser')->getAdapter()->getAdapter();
        } else {
            $current_adapter = Storage::disk('s3browser')->getAdapter();
        }

        if (!($current_adapter instanceof \League\Flysystem\AwsS3v3\AwsS3Adapter)) {
            throw new StorageException("current storage system doesn't support this operation");
        }

        $bucketListResponse = StorageConfig::createClient()->listBuckets();
        return $bucketListResponse['Buckets'];
    }

    // list object keys
    public function listObjects($bucket)
    {
        return Storage::disk('s3browser')->allFiles();
    }

    // list object keys in a prefix
    public function listPrefixedObjects($bucket, $prefix)
    {
        return Storage::disk('s3browser')->allFiles($prefix);
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
        $mime_type = Storage::disk('s3browser')->mimeType($object_key);

        // get object
        $object = Storage::disk('s3browser')->get($object_key);

        if (!$object) {
            throw new StorageException("failed to retrieve object");
        }

        // send object back
        return [
            "ContentType" => $mime_type,
            "Body" => $object
        ];
    }

    // put the desired object by key and path
    public function putObject($bucket, $object_key, $path, $mime_type)
    {
        // upload the file
        $stream = fopen($path, 'r+');
        $response = Storage::disk('s3browser')->putStream($object_key, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        // $response = Storage::disk('s3browser')->put($object_key, file_get_contents($path));
        if (!$response) {
            throw new StorageException("failed to upload object");
        }

        return [
            "@metadata" => [
                "statusCode" => 200
            ],
            "success" => $response
        ];
    }

    // delete the desired object by key
    public function deleteObject($bucket, $object_key)
    {
        // delete the file
        $response = Storage::disk('s3browser')->delete($object_key);
        if (!$response) {
            throw new StorageException("failed to delete object");
        }

        return [
            'DeleteMarker' => $response
        ];
    }

    // get all objects metadata within a prefix
    public function getObjects($bucket, $prefix)
    {
        $objectsListResponse = Storage::disk('s3browser')->listContents($prefix, true);

        $objects = [];

        if (isset($objectsListResponse)) {

            foreach ($objectsListResponse as $object) {

                if ($object['type'] == 'file') {

                    $unprefixed_key = $object['path'];

                    if ($prefix != '') {
                        $unprefixed_key = str_replace($prefix . '/', '', $object['path']);
                    }

                    $exploded_key = explode('/', $unprefixed_key);

                    if (count($exploded_key) == 1) {
                        // XXX FIXME: why does this not exist sometimes
                        $time_stamp = date(DATE_ISO8601);
                        if (array_key_exists('timestamp', $object))
                        {
                            $time_stamp = date(DATE_ISO8601, $object["timestamp"]);
                        }

                        // fill return details
                        $object['Key'] = $object["path"];
                        $object['ShortName'] = $object["basename"];
                        $object['Size'] = $object["size"];
                        $object['LastModified'] = $time_stamp;
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
            $current_adapter = Storage::disk('s3browser')->getAdapter()->getAdapter();
        } else {
            $current_adapter = Storage::disk('s3browser')->getAdapter();
        }

        if (!($current_adapter instanceof \League\Flysystem\AwsS3v3\AwsS3Adapter)) {
            throw new StorageException("current storage system doesn't support this operation");
        }

        // create the presigned URL
        $client = StorageConfig::createClient();
        $cmd = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $object_key
        ]);

        try {
            $request = $client->createPresignedRequest($cmd, $duration_str);
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
            $current_adapter = Storage::disk('s3browser')->getAdapter()->getAdapter();
        } else {
            $current_adapter = Storage::disk('s3browser')->getAdapter();
        }

        if (!($current_adapter instanceof \League\Flysystem\AwsS3v3\AwsS3Adapter)) {
            throw new StorageException("current storage system doesn't support this operation");
        }

        $parser = new PHPSQLParser();
        $parsed_query = $parser->parse($select_query);

        $client = StorageConfig::createClient();

        try {
            // retain header
            $result = $client->selectObjectContent([
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
            $result = $client->selectObjectContent([
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
