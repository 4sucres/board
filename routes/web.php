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

Route::get('/', 'DiscussionController@index')->name('home');

Route::get('/register', 'Auth\RegisterController@register')->name('register');
Route::post('/register', 'Auth\RegisterController@submit');
Route::get('/auth/verify_email/{token}', 'Auth\RegisterController@verify')->name('auth.verify_email');

Route::get('/login', 'Auth\LoginController@login')->name('login');
Route::post('/login', 'Auth\LoginController@submit');

Route::get('/d', 'DiscussionController@index')->name('discussions.index');
Route::get('/d/c/{category}-{slug}', 'DiscussionController@index')->name('discussions.categories.index');

Route::get('d/{discussion}-{slug}', 'DiscussionController@show')->name('discussions.show');
Route::get('/u/{user}-{name}', 'UserController@show')->name('user.show');

Route::get('/terms', 'HomeController@terms')->name('terms');
Route::get('/charter', 'HomeController@charter')->name('charter');
Route::get('/leaderboard', 'HomeController@leaderboard')->name('leaderboard');

Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

    Route::get('/profile', 'UserController@profile')->name('profile');
    Route::get('/notifications', 'NotificationController@index')->name('notifications.index');
    Route::get('/notifications/{notification}', 'NotificationController@show')->name('notifications.show');
    Route::get('/notifications/clear', 'NotificationController@clear')->name('notifications.clear');

    Route::get('/d/p', 'PrivateDiscussionController@index')->name('private_discussions.index');
    Route::get('/d/p/{user}-{name}/create', 'PrivateDiscussionController@create')->name('private_discussions.create');
    Route::post('/d/p/{user}-{name}', 'PrivateDiscussionController@store')->name('private_discussions.store');

    Route::get('/u/{user}-{name}/edit', 'UserController@edit')->name('user.edit');
    Route::put('/u/{user}-{name}', 'UserController@update')->name('user.update');

    Route::get('d/create', 'DiscussionController@create')->name('discussions.create');
    Route::post('d', 'DiscussionController@store')->name('discussions.store');
    Route::put('d/{discussion}-{slug}/update', 'DiscussionController@update')->name('discussions.update');
    Route::post('d/{discussion}-{slug}/create', 'DiscussionPostController@store')->name('discussions.posts.store');
    Route::get('d/{discussion}-{slug}/p/{post}/edit', 'DiscussionPostController@edit')->name('discussions.posts.edit');
    Route::get('d/{discussion}-{slug}/p/{post}/delete', 'DiscussionPostController@delete')->name('discussions.posts.delete');
    Route::put('d/{discussion}-{slug}/p/{post}', 'DiscussionPostController@update')->name('discussions.posts.update');
    // Route::post('d/{discussion}-{slug}/p/{post}/react', 'DiscussionPostController@react')->name('discussions.posts.react');
    Route::delete('d/{discussion}-{slug}/p/{post}', 'DiscussionPostController@destroy')->name('discussions.posts.destroy');

    Route::get('d/{discussion}-{slug}/subscribe', 'DiscussionController@subscribe')->name('discussions.subscribe');
    Route::get('d/{discussion}-{slug}/unsubscribe', 'DiscussionController@unsubscribe')->name('discussions.unsubscribe');
});
