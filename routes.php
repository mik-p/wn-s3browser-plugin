<?php //namespace mikp\s3browser\Routes;

$version = 'v1';
$api_name = '/s3browser';
$base_uri = '/api/' . $version . $api_name;

// api doc json file
Route::get($base_uri . '/api-docs.json', 'mikp\s3browser\Http\Controllers\API@docs');

// file browser api
Route::group([
    'prefix' => $base_uri,
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

    // get a pre-signed url for an object
    Route::get('/object/url', 'mikp\s3browser\Http\Controllers\API@presigned_url');

    // delete object
    Route::get('/delete', 'mikp\s3browser\Http\Controllers\API@delete_object');
    Route::delete('/delete', 'mikp\s3browser\Http\Controllers\API@delete_object');

    // download object as file
    Route::get('/download', 'mikp\s3browser\Http\Controllers\API@download');

    // upload objects as files
    Route::post('/upload', 'mikp\s3browser\Http\Controllers\API@upload');

    // download a zip file of objects
    Route::get('/zip', 'mikp\s3browser\Http\Controllers\API@zip');

    // use s3 select api on bucket objects
    Route::get('/select', 'mikp\s3browser\Http\Controllers\API@select');
});

Route::group([
    'prefix' => $base_uri,
    'middleware' => [
        'api',
        // 'web',
        // 'Winter\User\Classes\AuthMiddleware'
        'mikp\s3browser\Http\Middleware\BucketPrefixInHeader'
        ]
    ], function () {

    // use tus resumable upload api on bucket objects
    Route::any('/tus/{any?}', 'mikp\s3browser\Http\Controllers\API@tus')->where('any', '.*');
});
