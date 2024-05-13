<?php

use App\Http\Controllers\API\Admin\AdminAuthController;
use App\Http\Controllers\API\Admin\AdminController;
use App\Http\Controllers\API\BlogPost\BlogPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// route for Admin authentication for web
Route::group(['prefix' => 'v1/admin/auth'], function () {
    Route::controller(AdminAuthController::class)->group(function () {
        Route::post('/authenticate', 'authenticate');
        Route::post('/forgot_password', 'forgotPassword');
        Route::post('/reset_password', 'resetPassword');
        Route::post('/send_code', 'sendCode');
    });
});
// ends here


Route::middleware('auth:sanctum')->group(function () {
    Route::group(['prefix' => 'v1/admin'], function () {
        Route::controller(AdminController::class)->group(function () {
            Route::get('/logout', 'logout');


            Route::get('/post_list', 'postList');
            Route::get('/post_details/{id}', 'postDetails');

            Route::post('/create_blog_post', 'createPost');
            Route::post('/edit_blog_post', 'editPost');
            Route::delete('/delete_blog_post/{id}', 'deletePost');

            Route::post('/edit_profile', 'editProfile');
            Route::get('/fetch_profile', 'fetchProfile');
            Route::post('/change_password', 'changePassword');
            Route::post('/create_moderator', 'createModerator');
            Route::post('/delete_moderator', 'deleteModerator');
            Route::get('/fetch_admins', 'fetchAdmins');
            Route::post('/modify_moderator_role', 'modifyModeratorRole');
        });
    });
});

// route for Admin authentication for web
Route::group(['prefix' => 'v1/blog'], function () {
    Route::controller(BlogPostController::class)->group(function () {
        Route::get('/fetch_all_post', 'fetchBlogPost');
        Route::get('/blog_detail/{id}', 'blogDetail');
        Route::get('/fetch_top_post', 'fetchTopPost');
        Route::get('/fetch_related_post/{blog_id}', 'fetchRelatedPost');
        Route::post('/add_a_comment', 'addComment');
    });
});
// ends here
