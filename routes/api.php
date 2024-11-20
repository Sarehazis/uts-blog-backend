<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('jwt.auth');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);
// Route::get('/articles/{article}' )

Route::group(['middleware' => ['jwt.auth', 'role:admin']], function () {
    Route::post('/users/{id}/role', [AdminController::class, 'updateRole']);
    Route::get('/users', [UserController::class, 'users']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
});

Route::group(['middleware' => ['jwt.auth', 'role:writer']], function () {
    Route::post('/articles', [ArticleController::class, 'store']); 
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    Route::put('/articles/{article}', [ArticleController::class, 'update']);
});
Route::group(['middleware' => ['jwt.auth', 'role:reader']], function () {
   Route::post('/articles/{article}/like', [ArticleController::class, 'like']);
   Route::delete('/articles/{article}/unlike', [ArticleController::class, 'unlike']);
   Route::post('/articles/{article}/comment', [ArticleController::class, 'comment']);
});




Route::group(['middleware' => ['jwt.auth']], function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

