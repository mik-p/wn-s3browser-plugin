<?php namespace mikp\s3browser;

use System\Classes\PluginBase;

use App;
use Config;
use Storage;

use TusPhp\Tus\Server as TusServer;

use mikp\s3browser\Classes\StorageConfig;

use mikp\s3browser\Classes\PostResumableMove;

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

    public function register()
    {
        // set config from tus default
        Config::set('s3browser', \TusPhp\Config::get());

        // get new defaults from laravel config
        Config::set('s3browser.redis.host', Config::get('database.redis.default.host'));
        Config::set('s3browser.redis.port', Config::get('database.redis.default.port'));
        Config::set('s3browser.redis.database', '0');

        // set back tus config
        \TusPhp\Config::set(Config::get('s3browser'), true);

        // var_dump(Config::get('s3browser'));
        // var_dump(\TusPhp\Config::get());

        // setup local storage for resumable uploads
        $resume_dir = 's3browser-resumable';
        // Storage::makeDirectory($resume_dir);

        // try add tus protocol server
        App::singleton('tus-server', function ($app) use ($resume_dir) {
            $server = new TusServer(Config::get('cache.default', 'file'));

            // add post upload move to repository
            $listener = new PostResumableMove();
            $server->event()->addListener('tus-server.upload.complete', [$listener, 'postUploadOperation']);

            $server
                ->setApiPath('/api/v1/s3browser/tus') // tus server endpoint.
                ->setUploadDir(storage_path('app/'.$resume_dir)); // uploads dir.

            return $server;
        });
    }
}
