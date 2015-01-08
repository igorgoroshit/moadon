<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: token');


Route::get('/', function(){ return Redirect::to('admin'); });
Route::group(array('prefix' => 'admin'), function()
{

	Route::get('/', function(){ return View::make('admin.index'); });
	
	Route::post('login',	'AdminLoginController@login');
	Route::get('logout',	'AdminLoginController@logout');
	Route::post('restore',	'AdminLoginController@restore');

	Route::group(array('before' => 'auth'), function() 
	{
		Route::resource('clubs','AdminClubsController');
		Route::resource('members','AdminMembersController');
		Route::resource('suppliers','AdminSuppliersController');
		Route::resource('items','AdminItemsController');
		Route::resource('users','AdminUsersController');
		Route::resource('orders','AdminOrdersController');
		Route::resource('clients','AdminClientsController');
		Route::resource('categories','AdminCategoriesController');
		Route::resource('regions','AdminRegionsController');
		Route::resource('sitedetails','AdminSiteDetailsController');
		Route::post('{id}/uploadImage','AdminImagesController@uploadImage');
	});
});

Route::group(array('prefix' => 'clubs'), function()
{
	//temp 
	Route::get('supplier/{id}','ClubsController@supplier');
	Route::get('search','ClubsController@search');
	Route::group(array('before' => 'club_auth'), function() 
	{

	});
	Route::get('options','ClubsController@options');
	Route::post('login','LoginController@dologin');
	Route::post('restore','LoginController@restorePassword');
});

Route::get('options','OptionsController@options');