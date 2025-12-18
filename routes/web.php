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
    Route::resource('assignments', 'App\Http\Controllers\AssignmentController');

    // Client routes
    Route::resource('clients', 'App\Http\Controllers\ClientController');

    // Album routes
    Route::resource('albums', 'App\Http\Controllers\AlbumController');

    // Deliverable routes
    Route::resource('deliverables', 'App\Http\Controllers\DeliverableController');
    Route::post('assignments/{assignment}/deliverables/{deliverable}/status', 'App\Http\Controllers\DeliverableController@updateStatus');

    // Assignment relationship routes
    Route::post('assignment-relationships', 'App\Http\Controllers\AssignmentRelationshipController@store');
    Route::delete('assignment-relationships/{id}', 'App\Http\Controllers\AssignmentRelationshipController@destroy');

    // Lookup routes
    Route::get('api/lookup/music-types', 'App\Http\Controllers\LookupController@musicTypes');
    Route::get('api/lookup/music-keys', 'App\Http\Controllers\LookupController@musicKeys');
    Route::get('api/lookup/music-genres', 'App\Http\Controllers\LookupController@musicGenres');
    Route::get('api/lookup/music-creation-statuses', 'App\Http\Controllers\LookupController@musicCreationStatuses');
    Route::get('api/lookup/edit-types', 'App\Http\Controllers\LookupController@editTypes');
    Route::get('api/lookup/footage-types', 'App\Http\Controllers\LookupController@footageTypes');
    Route::get('api/lookup/release-timings', 'App\Http\Controllers\LookupController@releaseTimings');
    Route::get('api/lookup/departments', 'App\Http\Controllers\LookupController@departments');
    Route::get('api/lookup/deliverables', 'App\Http\Controllers\LookupController@deliverables');
});
