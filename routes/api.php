<?php

use App\Http\Controllers\Api\Auth\ApiSocialAuthController;
use App\Http\Controllers\Api\Auth\CustomLoginController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegionsController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return response()->json(
        ['message' => 'hello'],
    );
});

Route::get('/v1/categories', [CategoryController::class, 'allCategories']);

Route::get('/v1/categories', [CategoryController::class, 'allCategories']);

Route::get('/v1/{categoryId}/subcategories', [CategoryController::class, 'fetchSubcategories']);

Route::get('/v1/{subcategoryId}/products', [ProductController::class, 'getProducts']);

Route::get('v1/{subcategoryId}/attributes', [ProductController::class, 'getFilteredProducts']);
Route::post('/v1/auth/google/', [ApiSocialAuthController::class, 'googleSignIn']);
Route::post('/v1/auth/facebook/', [ApiSocialAuthController::class, 'facebookSignIn']);
Route::post('v1/auth/register/', [CustomLoginController::class, 'register']);
Route::post('v1/auth/login/', [CustomLoginController::class, 'login']);

Route::get('/v1/regions', [RegionsController::class, 'fetchRegions']);
Route::get('/v1/{parentRegionId}/child_regions', [RegionsController::class, 'fetchChildRegions']);


Route::get('/v1/filter-products/', [ProductController::class, 'filterProducts']);

Route::middleware('auth')->group(function () {
    Route::get('/profile/{id}', [ProfileController::class, 'getUserDataJson']);
    Route::post('/profile/create', [ProfileController::class, 'storeProfile']);
    Route::put('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::delete('/profile/delete', [ProfileController::class, 'deleteProfile']);
});

