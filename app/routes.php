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

$domain = getenv('ROOTDOMAIN');
if(empty($domain))
	$domain = 'moadonofesh.co.il';

Route::group(array('domain' => "{subdomain}.$domain"), function(){
	Route::get('/', 'SiteIndexController@index');
	Route::get('options', 'SiteClubsController@options');
	Route::post('login', 'SiteClubsController@login');
	Route::get('logout', 'SiteClubsController@logout');

	Route::post('account/login', 'SiteClientController@login');
	Route::post('account/register', 'SiteClientController@register');
	Route::get('account/logout', 'SiteClientController@logout');

	Route::group(array('before' => 'TokenAuth'), function(){
		Route::get('supplier/{id}','SiteClubsController@supplier');
		Route::get('search', 'SiteClubsController@search');
		Route::post('cart','SiteCartController@cart');
		
		Route::group(array('before' => 'clientAuth'), function(){
			Route::post('info/update', 'SiteClientController@updateInfo');
			Route::get('orders', 'SiteClientController@orders');
			Route::get('order/{id}', 'SiteClientController@order');
			Route::post('purchase', 'SiteClientController@purchase');
		});
	});

});

Route::group(array('prefix' => 'admin'), function()
{

	Route::get('/', function(){ return View::make('admin.index'); });
	
	Route::post('login',	'AdminLoginController@login');
	Route::get('logout',	'AdminLoginController@logout');
	Route::post('restore',	'AdminLoginController@restore');
	Route::get('options','AdminOptionsController@index');

	Route::group(array('before' => 'auth'), function() 
	{
		Route::resource('clubs','AdminClubsController');
		Route::resource('members','AdminMembersController');
		Route::resource('suppliers','AdminSuppliersController');
		Route::resource('items','AdminItemsController');
		Route::resource('users','AdminUsersController');
		Route::resource('cities','AdminCitiesController');
		Route::resource('orders','AdminOrdersController');
		Route::resource('clients','AdminClientsController');
		Route::resource('categories','AdminCategoriesController');
		Route::resource('regions','AdminRegionsController');
		Route::resource('sitedetails','AdminSiteDetailsController');
		Route::post('sitedetails/minisite/{id}','AdminSiteDetailsController@miniSite');
		Route::post('{id}/uploadImage','AdminImagesController@uploadImage');

		Route::resource('pages','AdminPagesController');
	});

});



