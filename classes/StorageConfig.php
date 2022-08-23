<?php

namespace mikp\s3browser\Classes;

use mikp\s3browser\Classes\StorageException;

use mikp\s3browser\Models\Settings;

class StorageConfig
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
        return StorageConfig::createConfig();
    }

    // static helpers
    public static function createConfig()
    {
        // choose an adapter from settings
        if (Settings::get('s3enable', false)) {
            // s3
            return [
                'driver' => 's3',
                'version' => 'latest',
                'region'  => Settings::get('s3region', 'us-east-1'),
                'endpoint' => Settings::get('s3url', 'no-url'),
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => Settings::get('s3accesskey', 'no-access'),
                    'secret' => Settings::get('s3secretkey', 'no-secret'),
                ],
            ];
        }
        elseif (Settings::get('gcpenable', false)) {
            // gcp
            // XXX FIXME: no credentials
            return [
                'driver' => 'gcp',
                'bucket' => Settings::get('gcpbucketname', 'no-bucket')
            ];
        }
        elseif (Settings::get('webdavenable', false)) {
            // webdav
            return [
                'driver' => 'webdav',
                'baseUri' => Settings::get('webdavuri', 'hostname'),
                'userName' => Settings::get('webdavuser', 'username'),
                'password' => Settings::get('webdavpassword', 'password')
            ];
        }
        elseif (Settings::get('ftpenable', false)) {
            // ftp
            return [
                'driver' => 'ftp',
                'host' => Settings::get('ftphost', 'hostname'),
                'root' => Settings::get('ftproot', '/root/path/'),
                'username' => Settings::get('ftpuser', 'username'),
                'password' => Settings::get('ftppassword', 'password')
            ];
        }
        else {
            // local
            return [
                'driver' => 'local',
                'root' => storage_path('app/s3browser/')
            ];
        }
    }

    // create a client from settings
    public static function createClient()
    {
        $config = StorageConfig::createConfig();

        if (Settings::get('s3enable', false)) {
            // s3
            return new \Aws\S3\S3Client($config);
        }

        return null;
    }

    // create an adapter from settings
    public static function createAdapter()
    {
        // choose an adapter from settings
        $config = StorageConfig::createConfig();

        if (Settings::get('s3enable', false)) {
            // s3
            return new \League\Flysystem\AwsS3v3\AwsS3Adapter(
                new \Aws\S3\S3Client($config),
                Settings::get('s3bucketname', 'no-bucket')
            );
        }
        elseif (Settings::get('gcpenable', false)) {
            // gcp
            // XXX FIXME: no credentials
            return new \Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter(
                new \Google\Cloud\Storage\StorageClient($config),
                $storageClient->bucket(Settings::get('gcpbucketname', 'no-bucket'))
            );
        }
        elseif (Settings::get('webdavenable', false)) {
            // webdav
            return new \League\Flysystem\WebDAV\WebDAVAdapter(
                new \Sabre\DAV\Client($config)
            );
        }
        elseif (Settings::get('ftpenable', false)) {
            // ftp
            return new \League\Flysystem\Adapter\Ftp($config);
        }
        else {
            // local
            return new \League\Flysystem\Adapter\Local(
                $config['root']
            );
        }
    }

    // create the storage filesystem
    public static function createFilesystem()
    {
        // create a filesystem
        if (Settings::get('s3usecache', false)) {
            // create a cache store
            // add cache to filesystem adapter
            return new \League\Flysystem\Filesystem(
                new \League\Flysystem\Cached\CachedAdapter(
                    StorageConfig::createAdapter(),
                    new \League\Flysystem\Cached\Storage\Memory()
                )
            );
        }

        return new \League\Flysystem\Filesystem(StorageConfig::createAdapter());
    }
}
