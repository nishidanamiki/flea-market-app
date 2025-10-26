<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
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
})->name('mypage');
Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('mypage.profile');
Route::patch('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('auth');

Route::get('/', [ItemController::class, 'index'])->name('items.index');



Route::get('items/sell', [ItemController::class, 'create'])->middleware('auth')->name('items.create');

Route::post('/items', [ItemController::class, 'store'])->middleware('auth')->name('items.store');
