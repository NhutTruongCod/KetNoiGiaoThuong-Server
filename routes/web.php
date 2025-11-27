<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
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

Route::get('/',function(){
    return view('welcome');
});

Route::get('/san-pham', function () {
    $products = Product::with(['shop', 'productImages'])
        ->where('status', 'active')
        ->take(12)
        ->get();
    return view('products', compact('products'));
});



Route::get('/momo/callback', function () {
    // MOMO sẽ gọi về đây sau khi thanh toán
    return "Thanh toán thành công! Cảm ơn bạn.";
})->name('momo.callback');


