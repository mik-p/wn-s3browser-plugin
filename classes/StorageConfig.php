<?php

namespace mikp\s3browser\Classes;

use Config;

use mikp\s3browser\Classes\StorageException;

use mikp\s3browser\Models\Settings;

class StorageConfig
{
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
            $config = [
                'driver' => 's3',
                // 'version' => 'latest',
                'version' => '2006-03-01',
                'region'  => Settings::get('s3region', 'us-east-1'),
                'endpoint' => Settings::get('s3url', 'no-url'),
                'use_path_style_endpoint' => true,
                // 'scheme' => Settings::get('s3https', false) ? 'https' : 'http',
                // 'http' => [
                //     'verify' => false
                // ],
                'credentials' => [
                    'key'    => Settings::get('s3accesskey', 'no-access'),
                    'secret' => Settings::get('s3secretkey', 'no-secret'),
                ],
                'key'    => Settings::get('s3accesskey', 'no-access'),
                'secret' => Settings::get('s3secretkey', 'no-secret'),
                'bucket' => Settings::get('s3bucketname', 'no-bucket')
            ];
        }
        elseif (Settings::get('gcpenable', false)) {
            // gcp
            // XXX FIXME: no credentials
            $config = [
                'driver' => 'gcp',
                'bucket' => Settings::get('gcpbucketname', 'no-bucket')
            ];
        }
        elseif (Settings::get('webdavenable', false)) {
            // webdav
            $config = [
                'driver' => 'webdav',
                'baseUri' => Settings::get('webdavuri', 'hostname'),
                'userName' => Settings::get('webdavuser', 'username'),
                'password' => Settings::get('webdavpassword', 'password')
            ];
        }
        elseif (Settings::get('ftpenable', false)) {
            // ftp
            $config = [
                'driver' => 'ftp',
                'host' => Settings::get('ftphost', 'hostname'),
                'root' => Settings::get('ftproot', '/root/path/'),
                'username' => Settings::get('ftpuser', 'username'),
                'password' => Settings::get('ftppassword', 'password')
            ];
        }
        else {
            // local
            $config = [
                'driver' => 'local',
                'root' => storage_path('app/s3browser/')
            ];
        }

        // add cache options
        if (Settings::get('s3usecache', false)) {
            $config['cache'] = StorageConfig::createCacheConfig();
        }

        return $config;
    }

    // create cache configuration
    public static function createCacheConfig()
    {
        return [
            'store' => Config::get('cache.default', 'file'),
            'expire' => 60,
            'prefix' => 's3browser',
        ];
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
                $config['bucket']
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

    // create cache store
    public static function createCacheStore()
    {
        $config = StorageConfig::createConfig();

        if ($config['cache'] === 'redis') {
            return new \League\Flysystem\Cached\Storage\Predis();
        }

        if ($config['cache'] === 'memcached') {
            return new \League\Flysystem\Cached\Storage\Memcached();
        }

        return new \League\Flysystem\Cached\Storage\Memory();
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
                    StorageConfig::createCacheStore()
                )
            );
        }

        return new \League\Flysystem\Filesystem(StorageConfig::createAdapter());
    }
}
