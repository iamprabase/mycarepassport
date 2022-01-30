<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ResourceController;

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

//register new user
Route::post('/create-account', [AuthenticationController::class, 'createAccount']);
//login user
Route::post('/signin', [AuthenticationController::class, 'signin']);
//using middleware
Route::post('/password-reset-request', 'AuthenticationController@forgotPassword');
Route::post('/password-reset', 'AuthenticationController@passwordReset');
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/profile', function(Request $request) {
        $user = auth()->user();
        $user->avatar = $user->avatar ? config('app.url').\Storage::url($user->avatar) : NULL;
        return auth()->user();
    });
    Route::post('/update-profile', [AuthenticationController::class, 'updateProfile']);
    Route::post('/sign-out', [AuthenticationController::class, 'signout']);

    Route::post('/create-folder', [ResourceController::class, 'createFolder']);
    Route::get('/get-folder-details', [ResourceController::class, 'getFolderDetails']);
    Route::post('/upload-documents-to-folder', [ResourceController::class, 'uploadDocuments']);
    Route::get('/get-personal-details', [ResourceController::class, 'getPersonalDetails']);
    Route::post('/update-personal-details', [ResourceController::class, 'updatePersonalDetails']);
    Route::get('/my-uploaded-personal-documents', [ResourceController::class, 'getUploadPersonalDocuments']);
    Route::post('/upload-personal-documents', [ResourceController::class, 'uploadPersonalDocuments']);
    Route::post('/individual-folder-detail', [ResourceController::class, 'getInidividualFolderDetails']);
});
