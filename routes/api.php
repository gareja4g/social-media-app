<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\FollowerController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name("register");
Route::post('/login', [AuthController::class, 'login'])->name("login");
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name("logout");

    // User Profile
    Route::controller(UserController::class)
        ->prefix("/user")
        ->name("user.")
        ->group(function () {
            Route::get('/search', 'searchUser')->name("search");
            Route::get('/', 'profile')->name("profile");
            Route::post('/update', 'update')->name("update");
            Route::put('/change-password', 'changePassword')->name("change.password");
        });

    // Posts
    Route::controller(PostController::class)
        ->prefix("/posts")
        ->name("posts.")
        ->group(function () {
            Route::get('/', 'index')->name("index");
            Route::get('/my-post', 'getMyPosts')->name("my.posts");
            Route::get('/relevant-post', 'getRelevantPosts')->name("relevant.posts");
            Route::get('/comments/{id}', 'getPostComments')->name("comments");
            Route::post('/store', 'store')->name("store");
            Route::get('/show/{id}', 'show')->name("show");
            Route::post('/update/{id}', 'update')->name("update");
            Route::delete('/delete/{id}', 'deletePost')->name("destroy");
            Route::post('/like/{id}', 'likePost')->name("like");
            Route::post('/unlike/{id}', 'unlikePost')->name("unlike");
            Route::post('/comment/{id}', 'commentOnPost')->name("comment");
        });

    // Followers
    Route::controller(FollowerController::class)
        ->group(function () {
            Route::post('/follow/{id}', 'follow')->name("follow");
            Route::post('/unfollow/{id}', 'unfollow')->name("unfollow");
            Route::get('/followers/{id}', 'followers')->name("followers");
            Route::get('/following/{id}', 'following')->name("following");
        });

    Route::controller(NotificationController::class)
        ->prefix("/notifications")
        ->name("notifications.")
        ->group(function () {
            Route::get('/', 'getNotifications')->name("index");
            Route::post('/read/{id}', 'markAsRead')->name("read");
        });
});
