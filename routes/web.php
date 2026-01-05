<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
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


Auth::routes();

Route::middleware(['auth'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // Auth routes
    Route::get('/auth/user', 'App\Http\Controllers\AuthController@getCurrentUser');

    // Profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'App\Http\Controllers\ProfileController@index')->name('index');
        Route::put('/', 'App\Http\Controllers\ProfileController@update')->name('update');
    });


    Route::prefix('assignments')->name('assignments.')->group(function () {
        // Assignment routes
        Route::get('/get-assignments', 'App\Http\Controllers\AssignmentController@getAssignments');
        Route::get('/create', 'App\Http\Controllers\AssignmentController@create')->name('create');
        Route::get('/', 'App\Http\Controllers\AssignmentController@index')->name('index');
        Route::post('/', 'App\Http\Controllers\AssignmentController@store');
        Route::get('/{id}/edit', 'App\Http\Controllers\AssignmentController@edit')->name('edit');
        Route::get('/{id}', 'App\Http\Controllers\AssignmentController@show')->name('show');
        Route::get('/{id}/data', 'App\Http\Controllers\AssignmentController@getData')->name('data');
        Route::put('/{id}', 'App\Http\Controllers\AssignmentController@update');
        Route::delete('/{id}', 'App\Http\Controllers\AssignmentController@destroy');
        Route::get('/{id}/available-songs', 'App\Http\Controllers\AssignmentController@getAvailableSongs');

        Route::post('/{assignment}/deliverables/{deliverable}/status', 'App\Http\Controllers\DeliverableController@updateStatus');


    });
    // Deliverable routes
    Route::resource('deliverables', 'App\Http\Controllers\DeliverableController');

    // Assignment relationship routes
    Route::post('assignment-relationships', 'App\Http\Controllers\AssignmentRelationshipController@store');
    Route::delete('assignment-relationships/{id}', 'App\Http\Controllers\AssignmentRelationshipController@destroy');

    // User routes - grouped with prefix 'users'
    Route::prefix('users')->group(function () {
        // User management routes (super-admin only) - specific routes first
        Route::get('/', 'App\Http\Controllers\UserController@index')->name('users.index');
        Route::get('/list', 'App\Http\Controllers\UserController@getUsers');
        Route::get('/roles', 'App\Http\Controllers\UserController@getRoles');
        Route::get('/available-for-assignment', 'App\Http\Controllers\UserController@getAvailableForAssignment');
        Route::get('/by-department/{departmentId}', 'App\Http\Controllers\UserController@getByDepartment');
        Route::post('/', 'App\Http\Controllers\UserController@store');
        Route::put('/{id}', 'App\Http\Controllers\UserController@update');

        // Parameterized routes last to avoid conflicts
        Route::get('/{departmentId}', 'App\Http\Controllers\UserController@getByDepartment');
    });

    // Client routes
    Route::get('clients', 'App\Http\Controllers\ClientController@index');
    Route::post('clients', 'App\Http\Controllers\ClientController@store');
    Route::get('clients/{id}', 'App\Http\Controllers\ClientController@show');
    Route::put('clients/{id}', 'App\Http\Controllers\ClientController@update');
    Route::delete('clients/{id}', 'App\Http\Controllers\ClientController@destroy');

    // Album routes
    Route::get('albums', 'App\Http\Controllers\AlbumController@index');
    Route::post('albums', 'App\Http\Controllers\AlbumController@store');
    Route::get('albums/{id}', 'App\Http\Controllers\AlbumController@show');
    Route::put('albums/{id}', 'App\Http\Controllers\AlbumController@update');
    Route::delete('albums/{id}', 'App\Http\Controllers\AlbumController@destroy');

    // Artist routes
    Route::get('artists', 'App\Http\Controllers\AssignmentController@getArtists');
    Route::post('artists', 'App\Http\Controllers\AssignmentController@storeArtist');

    // Song routes
    Route::get('songs', 'App\Http\Controllers\SongController@index');
    Route::post('songs', 'App\Http\Controllers\SongController@store');
    Route::get('songs/{id}', 'App\Http\Controllers\SongController@show');
    Route::put('songs/{id}', 'App\Http\Controllers\SongController@update');
    Route::delete('songs/{id}', 'App\Http\Controllers\SongController@destroy');

    Route::get('music-types/{musicTypeId}/{departmentId}/completion-days', 'App\Http\Controllers\AssignmentController@getCompletionDays');

    // Assignment relationship routes
    Route::post('assignment-relationships', 'App\Http\Controllers\AssignmentRelationshipController@store');
    Route::delete('assignment-relationships/{id}', 'App\Http\Controllers\AssignmentRelationshipController@destroy');

    // Lookup routes
    Route::get('lookup/get-initial-data', 'App\Http\Controllers\LookupController@getInitialData');
    Route::get('lookup/departments', 'App\Http\Controllers\LookupController@departments');
    Route::get('lookup/deliverables', 'App\Http\Controllers\LookupController@deliverables');
    Route::get('lookup/child-departments', 'App\Http\Controllers\LookupController@childDepartments');
});
