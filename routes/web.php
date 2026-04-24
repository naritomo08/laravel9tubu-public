<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;

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

/*
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/', function () {
    return view('welcome');
});
*/

Route::get('/', function () {
    // redirect関数にパスを指定する方法
    return redirect('/tweet');
});

require __DIR__.'/auth.php';

//Route::get('/sample', [\App\Http\Controllers\Sample\IndexController::class, 'show']);

//Route::get('/sample/{id}', [\App\Http\Controllers\Sample\IndexController::class, 'showId']);

Route::get('/tweet', \App\Http\Controllers\Tweet\IndexController::class)->name('tweet.index');
Route::get('/tweet/latest', \App\Http\Controllers\Tweet\LatestController::class)->name('tweet.latest');
Route::get('/like/status', \App\Http\Controllers\Like\StatusController::class)->name('like.status');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/tweet/create', \App\Http\Controllers\Tweet\CreateController::class)
        ->name('tweet.create');
    Route::get('/tweet/update/{tweetId}', \App\Http\Controllers\Tweet\Update\IndexController::class)->name('tweet.update.index');
    Route::put('/tweet/update/{tweetId}', \App\Http\Controllers\Tweet\Update\PutController::class)->name('tweet.update.put');
    Route::delete('/tweet/delete/{tweetId}', \App\Http\Controllers\Tweet\DeleteController::class)->name('tweet.delete');
    Route::post('/like', \App\Http\Controllers\Like\LikeController::class)->name('like.toggle');

    Route::get('/account', [\App\Http\Controllers\Account\AccountController::class, 'index'])->name('account.index');
    Route::get('/account/stats', [\App\Http\Controllers\Account\AccountController::class, 'stats'])->name('account.stats');
    Route::put('/account/profile', [\App\Http\Controllers\Account\AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [\App\Http\Controllers\Account\AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::delete('/account', [\App\Http\Controllers\Account\AccountController::class, 'destroy'])->name('account.destroy');
});


// 管理画面（ユーザー管理）
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/stats', [\App\Http\Controllers\Admin\UserController::class, 'stats'])->name('admin.users.stats');
    Route::put('/admin/users/{user}/email', [\App\Http\Controllers\Admin\UserController::class, 'updateEmail'])->name('admin.users.email.update');
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
});

Route::get('/health', function (): JsonResponse {
    return response()->json(['status' => 'ok'], 200);
});
