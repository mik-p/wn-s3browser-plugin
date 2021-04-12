<?php namespace mikp\s3browser\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 's3-browser-backend-menu';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';
}