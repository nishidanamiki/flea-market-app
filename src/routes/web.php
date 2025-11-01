<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use GuzzleHttp\Middleware;

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

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::get('/mypage',function () {
    return view('mypage.index');
    })->middleware('auth')->name('mypage');
Route::get('/mypage/profile', [ProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::patch('/mypage/profile', [ProfileController::class, 'update'])->middleware('auth')->name('profile.update');

Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::get('/sell', [ItemController::class, 'create'])->middleware('auth')->name('items.create');
Route::post('/items', [ItemController::class, 'store'])->middleware('auth')->name('items.store');
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('items.show');
Route::get('/purchase/{item_id}', [PurchaseController::class, 'index'])->name('purchase');
Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');
Route::post('/items/{item}/like', [LikeController::class, 'store'])->name('items.like')->middleware('auth');
Route::delete('/items/{item}/like', [LikeController::class, 'destroy'])->name('items.unlike')->middleware('auth');
Route::post('/items/{item}/comment', [CommentController::class, 'store'])->middleware('auth')->name('comment.store');
