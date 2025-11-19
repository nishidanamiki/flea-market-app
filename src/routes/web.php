<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/mypage', [ProfileController::class, 'index'])->name('mypage');
    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::middleware('auth')->group(function () {
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
});
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('items.show');

Route::middleware('auth')->group(function () {
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'index'])->name('purchase');
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store'); //テスト用
    Route::get('purchase/address/{item_id}', [PurchaseController::class, 'editAddress'])->name('address.edit');
    Route::post('purchase/address/{item_id}', [PurchaseController::class, 'updateAddress'])->name('address.update');
    Route::post('/purchase/{item_id}/checkout', [PurchaseController::class, 'checkout'])->name('purchase.checkout'); //本番用
    Route::get('/purchase/success/{item}', [PurchaseController::class, 'success'])->name('purchase.success');
    Route::get('/purchase/cancel/{item}', [PurchaseController::class, 'cancel'])->name('purchase.cancel');
});

Route::middleware('auth')->group(function () {
    Route::post('/items/{item}/like', [LikeController::class, 'store'])->name('items.like');
    Route::delete('/items/{item}/like', [LikeController::class, 'destroy'])->name('items.unlike');
});

Route::middleware('auth')->group(function () {
    Route::post('/items/{item}/comment', [CommentController::class, 'store'])->name('comment.store');
});



Route::get('debug/success', function () {
    return view('purchase.success', ['item' => null, 'status' => 'success']);
});

Route::get('/debug/cancel', function () {
    return view('purchase.cancel');
});
