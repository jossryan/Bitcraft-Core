<?php

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

Route::get('/', function() {
  return redirect('portal');
});

Route::get('/portal', function () {
    return view('portal');
})->middleware('auth');

Auth::routes();

Route::get('/dashboard', 'DashboardController@index')->name('dashboard')->middleware('admin');
