<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/key', function() {
    return str_random(32);
});

$app->post('auth/login',['uses' => 'AuthController@authenticate']);
// $app->post('index',  ['middleware' => 'jwt.auth', 'uses' => 'IndexController@api']);
$app->post('/view',  ['middleware' => 'jwt.auth', 'uses' => 'ViewController@api']);
// $app->post('/store', ['middleware' => 'jwt.auth', 'uses' => 'StoreController@api']);
$app->post('index',  'IndexController@api');
// $app->post('/view',  'ViewController@api');
$app->post('/store', 'StoreController@api');
