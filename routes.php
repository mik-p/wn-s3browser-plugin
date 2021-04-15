<?php //namespace mikp\s3browser\Routes;

use mikp\s3browser\Models\Settings;

Route::group([
    'prefix' => '/s3browser/api/v1',
    'middleware' => [
        'api',
        'web',
        'RainLab\User\Classes\AuthMiddleware'
    ]], function() {

        Route::get('/', 'mikp\s3browser\Http\Controllers\API@index');

        Route::get('/download/{file}', 'mikp\s3browser\Http\Controllers\API@download');

        Route::post('/upload', 'mikp\s3browser\Http\Controllers\API@upload');
});
