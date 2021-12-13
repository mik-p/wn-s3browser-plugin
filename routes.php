<?php //namespace mikp\s3browser\Routes;

Route::group([
    'prefix' => '/api/v1/s3browser',
    'middleware' => [
        'api',
        // 'web',
        // 'Winter\User\Classes\AuthMiddleware'
    ]
], function () {

    // api index
    Route::get('/', 'mikp\s3browser\Http\Controllers\API@index');

    // list objects in bucket
    Route::get('/list/{bucket}', 'mikp\s3browser\Http\Controllers\API@list');

    // api get object contents in body
    Route::get('/object', 'mikp\s3browser\Http\Controllers\API@get_object');

    // api post object as s3 object
    Route::post('/object', 'mikp\s3browser\Http\Controllers\API@post_object');

    // download object as file
    Route::get('/download', 'mikp\s3browser\Http\Controllers\API@download');

    // upload objects as files
    Route::post('/upload', 'mikp\s3browser\Http\Controllers\API@upload');

    // download a zip file of objects
    Route::get('/zip', 'mikp\s3browser\Http\Controllers\API@zip');

    // use s3 select api on bucket objects
    Route::get('/select', 'mikp\s3browser\Http\Controllers\API@select');
});
