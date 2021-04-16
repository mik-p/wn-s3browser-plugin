<?php //namespace mikp\s3browser\Routes;

use mikp\s3browser\Models\Settings;

Route::group([
    'prefix' => '/s3browser/api/v1',
    'middleware' => [
        'api',
        'web',
        'RainLab\User\Classes\AuthMiddleware'
    ]], function() {

        // api index
        Route::get('/', 'mikp\s3browser\Http\Controllers\API@index');

        // api get object contents in body
        Route::get('/get', 'mikp\s3browser\Http\Controllers\API@get_object');

        // api post object as s3 object
        Route::post('/post', 'mikp\s3browser\Http\Controllers\API@post_object');

        // download object as file
        Route::get('/download', 'mikp\s3browser\Http\Controllers\API@download');

        // upload objects as files
        Route::post('/upload', 'mikp\s3browser\Http\Controllers\API@upload');
});
