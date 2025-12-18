<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Assignment routes
    Route::apiResource('assignments', 'App\Http\Controllers\AssignmentController');
    
    // Client routes
    Route::apiResource('clients', 'App\Http\Controllers\ClientController');
    
    // Album routes
    Route::apiResource('albums', 'App\Http\Controllers\AlbumController');
    
    // Deliverable routes
    Route::apiResource('deliverables', 'App\Http\Controllers\DeliverableController');
    Route::post('assignments/{assignment}/deliverables/{deliverable}/status', 'App\Http\Controllers\DeliverableController@updateStatus');
    
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
});
