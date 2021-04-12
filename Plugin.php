<?php namespace mikp\s3browser;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public $elevated = true;

	public $require = ['RainLab.User'];
	
    public function registerComponents()
    {
        return [
            'mikp\s3browser\Components\s3browser' => 's3browser'
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'S3 Browser',
                'description' => 'Manage S3 browser UI settings.',
                'icon'        => 'oc-icon-folder-open-o',
                'class'       => 'mikp\s3browser\Models\Settings',
                'order'       => 600,
                'keywords'    => 's3 data files',
                //'permissions' => ['acme.users.access_settings']
            ]
        ];
    }
}
