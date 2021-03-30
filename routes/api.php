<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/product/test', function (Request $request) {
    $productService = new \App\MicroApi\Services\ProductService();

    $product = new \App\MicroApi\Items\ProductItem();
    $product->brand_id = 1;
    $product->sku = \Illuminate\Support\Str::random(16);
    $product->name = "微服务架构";
    $product->slug = 'microservice';
    $product->description = "基于 Laravel + Go Micro 框架构建微服务系统";
    $product->cover = 'https://qcdn.xueyuanjun.com/wp-content/uploads/2019/06/94fe5973d09b0ad753082b6b1ba46f3d.jpeg';
    $product->price = 199;
    $product->sale_price = 99;
    $product->quantity = 1000;
    $newProduct = $productService->create($product);

    dd($productService->getById($newProduct->id, true));
});