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
$app->post('index',  ['middleware' => 'jwt.auth', 'uses' => 'IndexController@api']);
$app->post('/view',  ['middleware' => 'jwt.auth', 'uses' => 'ViewController@api']);
$app->post('/store', ['middleware' => 'jwt.auth', 'uses' => 'StoreController@api']);
$app->post('/cek', 'IndexController@api');
// $app->post('index',  'IndexController@api');
// $app->post('/view',  'ViewController@api');
// $app->post('/store', 'StoreController@api');

$app->post('/get-file', 'StoreController@testview_file');

$app->post('/tree-menu/{roll_id}', 'ViewController@menuTree');

$app->get('/print/uper/{id}','ViewController@printUper');
$app->get('/print/proforma/{id}','ViewController@printProforma');
$app->get('/print/getPass/{id}','ViewController@printGetPass');
$app->get('/print/uper2/{id}','ViewController@printUper2');
$app->get('/print/proforma2/{id}','ViewController@printProforma2');
$app->get('/print/invoice2/{id}','ViewController@printInvoice');
