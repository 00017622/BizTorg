<?php

use App\Http\Controllers\Api\Auth\ApiSocialAuthController;
use App\Http\Controllers\Api\Auth\CustomLoginController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConversationsController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegionsController;
use App\Http\Controllers\ShopRatingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ShopProfileController;
use App\Http\Controllers\ShopSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/home', [CategoryController::class, 'homePage']);

Route::get('/v1/categories', [CategoryController::class, 'fetchCategories']);

Route::get('/v1/{categoryId}/subcategories', [CategoryController::class, 'fetchSubcategories']);

Route::get('/v1/{subcategoryId}/products', [ProductController::class, 'getProducts']);

Route::get('v1/{subcategoryId}/attributes', [ProductController::class, 'getAttributes']);
Route::post('/v1/auth/google/', [ApiSocialAuthController::class, 'googleSignIn']);
Route::post('/v1/auth/facebook/', [ApiSocialAuthController::class, 'facebookSignIn']);
Route::post('v1/auth/register/', [CustomLoginController::class, 'register']);
Route::post('v1/auth/login/', [CustomLoginController::class, 'login']);

Route::get('/v1/regions', [RegionsController::class, 'fetchRegions']);
Route::get('/v1/{parentRegionId}/child_regions', [RegionsController::class, 'fetchChildRegions']);


Route::get('/v1/filter-products/', [ProductController::class, 'filterProducts']);

Route::get('/v1/profile/{id}', [ProfileController::class, 'getUserDataJson']);
Route::post('/v1/profile/update', [ProfileController::class, 'updateProfile']);

Route::post('/v1/product/create', [ProductController::class, 'createProduct']);

Route::get('/v1/product/{productId}', [ProductController::class, 'getProduct']);
Route::get('/v1/product/slug/{productSlug}', [ProductController::class, 'getProductBySlug']);

Route::get('/v1/favorites/', [ProductController::class, 'getFavorite'])->middleware('auth:sanctum');;
Route::post('/v1/favorite/toggle/', [ProductController::class, 'toggleFavorites'])->middleware('auth:sanctum');
Route::post('v1/send/message/', [MessagesController::class, 'sendMessage'])->middleware('auth:sanctum');

Route::get('v1/getMessages/{receiver_id}', [MessagesController::class, 'getMessages'])->middleware('auth:sanctum');

Route::post('v1/store-fcm-token', [CustomLoginController::class, 'storeFcmToken']);
Route::post('v1/clear-fcm-token', [CustomLoginController::class, 'clearFcmToken']);

Route::get('v1/user/{id}', [CustomLoginController::class, 'show']);
Route::get('v1/user/{id}/fcm-token', [CustomLoginController::class, 'getFcmToken']);

Route::get('/v1/user/{uuid}/products', [ProductController::class, 'getUserProducts'])->middleware('auth:sanctum');
Route::delete('/v1/products/delete/{productId}', [ProductController::class, 'removeProduct'])->middleware('auth:sanctum');
Route::get('/v1/fetch/product/{id}', [ProductController::class, 'fetchSingleProduct']);

Route::post('/v1/product/update/{id}', [ProductController::class, 'updateProduct'])->middleware('auth:sanctum');

Route::delete('/v1/product/image/{id}', [ProductController::class, 'deleteImage'])->middleware('auth:sanctum');

Route::get('/v1/user/favorites/{uuid}', [ProductController::class, 'getFavoritesOfUser'])->middleware('auth:sanctum');
Route::get('/v1/user/get/chat/conversations', [ConversationsController::class, 'getChats'])->middleware('auth:sanctum');

Route::get('/v1/search-recommendations', [CategoryController::class, 'searchRecommendations']);

Route::get('/v1/search', [CategoryController::class, 'searchProducts']);

Route::get('v1/category/{categoryId}/products', [ProductController::class, 'getProductsByCategory']);

Route::get('/v1/find-category/subcategory/{id}', [CategoryController::class, 'getCategory']);

Route::get('/v1/notifications', [NotificationsController::class, 'index'])->middleware('auth:sanctum');

Route::post('/v1/notifications/mark-all-seen', [NotificationsController::class, 'markAsSeen'])->middleware('auth:sanctum');

Route::post('/v1/notifications/mark-seen-for-chat', [NotificationsController::class, 'markSeenForChat'])->middleware('auth:sanctum');

Route::post('/v1/auth/send-verification-code', [CustomLoginController::class, 'sendVerificationCode']);

Route::post('/v1/auth/verify-and-register', [CustomLoginController::class, 'verifyAndRegister']);

Route::post('/v1/shop-profiles/', [ShopProfileController::class, 'store'])->middleware('auth:sanctum');

Route::post('/v1/shop/update', [ShopProfileController::class, 'updateShopData'])->middleware('auth:sanctum');

Route::post('/v1/{shopId}/upload-profile-images/', [ShopProfileController::class, 'updateImages'])->middleware('auth:sanctum');

Route::get('/v1/{shopId}/getShop', [ShopProfileController::class, 'getShopProfile'])->middleware('auth:sanctum');

Route::post('/v1/subscribe/{shopId}', [ShopSubscriptionController::class, 'subscribe'])->middleware('auth:sanctum');
Route::post('/v1/unsubscribe/{shopId}', [ShopSubscriptionController::class, 'unsubscribe'])->middleware('auth:sanctum');

Route::get('/v1/shops/{userId}', [ShopProfileController::class, 'getUserProducts']);



Route::post('/v1/shop/rate', [ShopRatingController::class, 'rateShop'])->middleware('auth:sanctum');

Route::post('/v1/upload/chat-image/', [MessagesController::class, 'uploadChatImage'])->middleware('auth:sanctum');