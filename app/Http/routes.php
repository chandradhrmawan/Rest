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

// Routing
$app->post('auth/login',['uses' => 'AuthController@authenticate']);
$app->post('index',  ['middleware' => 'jwt.auth', 'uses' => 'IndexController@api']);
$app->post('/view',  ['middleware' => 'jwt.auth', 'uses' => 'ViewController@api']);
$app->post('/store', ['middleware' => 'jwt.auth', 'uses' => 'StoreController@api']);
$app->post('/cek', 'IndexController@api');

// Test Route
$app->post('/get-file', 'StoreController@testview_file');
$app->post('/tree-menu/{roll_id}' , 'ViewController@menuTree');
$app->get('/export_testlainlain'  ,'ViewController@exportToExcel');

// Print
$app->get('/print/getPass/{id}'   ,'ViewController@printGetPass');
$app->get('/print/uper2/{id}'     ,'ViewController@printUper2');
$app->get('/print/proforma2/{id}' ,'ViewController@printProforma2');
$app->get('/print/invoice2/{id}'  ,'ViewController@printInvoice');
$app->get('/print/uperPaid2/{id}' ,'ViewController@printUperPaid');
$app->get('/print/bprp2/{id}'     ,'ViewController@printBprp');
$app->get('/print/realisasi2/{id}','ViewController@printRealisasi');

// Export
$app->get('/export/debitur/{branchId}/{notaNo}/{custName}/{layanan}/{startDate}/{endDate}/{branchCode}'      ,'ViewController@ExportDebitur');
$app->get('/export/rekonsilasi/{branchId}/{vessel}/{ukk}/{nota}/{startDate}/{endDate}/{branchCode}'          ,'ViewController@ExportRekonsilasi');
$app->get('/export/pendapatan/{branchId}/{kemasan}/{komoditi}/{satuan}/{startDate}/{endDate}/{branchCode}'   ,'ViewController@ExportPendapatan');
