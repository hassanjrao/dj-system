<?php

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

    // Assignment routes
    Route::get('assignments/get-assignments', 'App\Http\Controllers\AssignmentController@getAssignments');
    Route::get('assignments/create', 'App\Http\Controllers\AssignmentController@create')->name('assignments.create');
    Route::get('assignments', 'App\Http\Controllers\AssignmentController@index')->name('assignments.index');
    Route::post('assignments', 'App\Http\Controllers\AssignmentController@store');
    Route::get('assignments/{id}/edit', 'App\Http\Controllers\AssignmentController@edit')->name('assignments.edit');
    Route::get('assignments/{id}', 'App\Http\Controllers\AssignmentController@show')->name('assignments.show');
    Route::put('assignments/{id}', 'App\Http\Controllers\AssignmentController@update');
    Route::delete('assignments/{id}', 'App\Http\Controllers\AssignmentController@destroy');
    Route::get('assignments/{id}/available-songs', 'App\Http\Controllers\AssignmentController@getAvailableSongs');

    // Deliverable routes
    Route::resource('deliverables', 'App\Http\Controllers\DeliverableController');
    Route::post('assignments/{assignment}/deliverables/{deliverable}/status', 'App\Http\Controllers\DeliverableController@updateStatus');

    // Assignment relationship routes
    Route::post('assignment-relationships', 'App\Http\Controllers\AssignmentRelationshipController@store');
    Route::delete('assignment-relationships/{id}', 'App\Http\Controllers\AssignmentRelationshipController@destroy');

    // User routes
    Route::get('users/{departmentId}', 'App\Http\Controllers\UserController@getByDepartment');


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

    // User routes
    Route::get('users/by-department/{departmentId}', 'App\Http\Controllers\UserController@getByDepartment');
    Route::get('users/available-for-assignment', 'App\Http\Controllers\UserController@getAvailableForAssignment');

    // Artist routes
    Route::get('artists', 'App\Http\Controllers\AssignmentController@getArtists');
    Route::post('artists', 'App\Http\Controllers\AssignmentController@storeArtist');

    // Song routes
    Route::get('songs', 'App\Http\Controllers\SongController@index');
    Route::post('songs', 'App\Http\Controllers\SongController@store');
    Route::get('songs/{id}', 'App\Http\Controllers\SongController@show');
    Route::put('songs/{id}', 'App\Http\Controllers\SongController@update');
    Route::delete('songs/{id}', 'App\Http\Controllers\SongController@destroy');

    // Deliverable routes
    Route::get('deliverables', 'App\Http\Controllers\DeliverableController@index');
    Route::get('deliverables/pre-select', 'App\Http\Controllers\DeliverableController@preSelect');
    Route::get('music-types/{id}/completion-days', 'App\Http\Controllers\AssignmentController@getCompletionDays');

    // Assignment relationship routes
    Route::post('assignment-relationships', 'App\Http\Controllers\AssignmentRelationshipController@store');
    Route::delete('assignment-relationships/{id}', 'App\Http\Controllers\AssignmentRelationshipController@destroy');

    // Lookup routes
    Route::get('lookup/music-types', 'App\Http\Controllers\LookupController@musicTypes');
    Route::get('lookup/music-keys', 'App\Http\Controllers\LookupController@musicKeys');
    Route::get('lookup/music-genres', 'App\Http\Controllers\LookupController@musicGenres');
    Route::get('lookup/music-creation-statuses', 'App\Http\Controllers\LookupController@musicCreationStatuses');
    Route::get('lookup/edit-types', 'App\Http\Controllers\LookupController@editTypes');
    Route::get('lookup/footage-types', 'App\Http\Controllers\LookupController@footageTypes');
    Route::get('lookup/release-timings', 'App\Http\Controllers\LookupController@releaseTimings');
    Route::get('lookup/departments', 'App\Http\Controllers\LookupController@departments');
    Route::get('lookup/deliverables', 'App\Http\Controllers\LookupController@deliverables');
    Route::get('lookup/child-departments', 'App\Http\Controllers\LookupController@childDepartments');
});
