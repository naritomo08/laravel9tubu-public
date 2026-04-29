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

Route::get('/terms', [\App\Http\Controllers\LegalDocumentController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [\App\Http\Controllers\LegalDocumentController::class, 'privacy'])->name('legal.privacy');

require __DIR__.'/auth.php';

//Route::get('/sample', [\App\Http\Controllers\Sample\IndexController::class, 'show']);

//Route::get('/sample/{id}', [\App\Http\Controllers\Sample\IndexController::class, 'showId']);

Route::get('/tweet', \App\Http\Controllers\Tweet\IndexController::class)->name('tweet.index');
Route::get('/tweet/latest', \App\Http\Controllers\Tweet\LatestController::class)->name('tweet.latest');
Route::get('/like/status', \App\Http\Controllers\Like\StatusController::class)->name('like.status');
Route::middleware('auth')->get('/account/admin-status', function () {
    return response()->json([
        'is_admin' => (bool) request()->user()->is_admin,
    ]);
})->name('account.admin.status');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tweet/search', [\App\Http\Controllers\Tweet\SearchController::class, 'index'])->name('tweet.search');
    Route::get('/tweet/search/results', [\App\Http\Controllers\Tweet\SearchController::class, 'results'])->name('tweet.search.results');
    Route::post('/tweet/create', \App\Http\Controllers\Tweet\CreateController::class)
        ->name('tweet.create');
    Route::get('/tweet/update/{tweetId}', \App\Http\Controllers\Tweet\Update\IndexController::class)->name('tweet.update.index');
    Route::put('/tweet/update/{tweetId}', \App\Http\Controllers\Tweet\Update\PutController::class)->name('tweet.update.put');
    Route::delete('/tweet/delete/{tweetId}', \App\Http\Controllers\Tweet\DeleteController::class)->name('tweet.delete');
    Route::put('/tweet/protection/{tweet}', \App\Http\Controllers\Tweet\ProtectionController::class)->name('tweet.protection');
    Route::post('/like', \App\Http\Controllers\Like\LikeController::class)->name('like.toggle');

    Route::get('/account', [\App\Http\Controllers\Account\AccountController::class, 'index'])->name('account.index');
    Route::get('/account/google/connect', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirectForLink'])->name('account.google.connect');
    Route::get('/account/stats', [\App\Http\Controllers\Account\AccountController::class, 'stats'])->name('account.stats');
    Route::put('/account/profile', [\App\Http\Controllers\Account\AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [\App\Http\Controllers\Account\AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::delete('/account/google', [\App\Http\Controllers\Account\AccountController::class, 'disconnectGoogle'])->name('account.google.disconnect');
    Route::delete('/account', [\App\Http\Controllers\Account\AccountController::class, 'destroy'])->name('account.destroy');
});


// 管理画面（ユーザー管理）
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/stats', [\App\Http\Controllers\Admin\UserController::class, 'stats'])->name('admin.users.stats');
    Route::get('/admin/users/list', [\App\Http\Controllers\Admin\UserController::class, 'listUsers'])->name('admin.users.list');
    Route::put('/admin/users/{user}/email', [\App\Http\Controllers\Admin\UserController::class, 'updateEmail'])->name('admin.users.email.update');
    Route::put('/admin/users/{user}/admin', [\App\Http\Controllers\Admin\UserController::class, 'updateAdmin'])->name('admin.users.admin.update');
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
});

Route::get('/health', function (): JsonResponse {
    return response()->json(['status' => 'ok'], 200);
});
