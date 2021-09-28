<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/batches', function () {
    $client = new \MailchimpMarketing\ApiClient();
    $client->setConfig([
        'apiKey' => config('constants.apiKey', '06fb76c7ec26d4f6055ec2d475d9a2f9-us5'),
        'server' => config('constants.server', 'us5'),
    ]);

    $batches = $client->batches->list($fields = null, $exclude_fields = null, $count = '1000');
    dd($batches);
});

Route::get('test', 'HomeController@testApi');
Route::post('import-contacts', 'HomeController@importContacts')->name('import_contacts');
Route::post('update-contacts', 'HomeController@updateContacts')->name('update_contacts');
Route::post('sync-tags', 'HomeController@syncTagsOfMembers')->name('sync_tags');
Route::get('export-list', 'HomeController@getListOfMembers')->name('export_list');
