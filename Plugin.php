<?php namespace mikp\s3browser;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public $elevated = true;

	public $require = ['Winter.User'];

    public function registerComponents()
    {
        return [
            'mikp\s3browser\Components\s3browser' => 's3browser',
            'mikp\s3browser\Components\s3uploader' => 's3uploader',
            'mikp\s3browser\Components\s3query' => 's3query'
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
}
