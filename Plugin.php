<?php namespace mikp\s3browser;

use System\Classes\PluginBase;

use Config;
use Storage;

use mikp\s3browser\Classes\StorageConfig;

class Plugin extends PluginBase
{
    public $elevated = true;

    public function registerComponents()
    {
        return [
            'mikp\s3browser\Components\S3Browser' => 's3browser',
            'mikp\s3browser\Components\S3Uploader' => 's3uploader',
            'mikp\s3browser\Components\S3Query' => 's3query'
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'S3 Browser',
                'description' => 'Manage S3 browser UI settings.',
                'icon'        => 'wn-icon-folder-open-o',
                'class'       => 'mikp\s3browser\Models\Settings',
                'order'       => 600,
                'keywords'    => 's3 data files',
                'permissions' => ['mikp.s3browser.settings']
            ]
        ];
    }

    public function boot()
    {
        // set config
        // Config::set('filesystems.disks.s3browser', Config::get('mikp.s3browser::config'));
        Config::set('filesystems.disks.s3browser', StorageConfig::createConfig());

        // extend user storage driver
        Storage::extend('s3browser', function ($app, $config) {
            // get the configured adapter
            $adapter = (new StorageClient())->createAdapter();

            // create a filesystem
            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \League\Flysystem\Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
