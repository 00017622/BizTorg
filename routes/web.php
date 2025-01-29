<?php

use App\Http\Controllers\Api\Auth\SocialApiAuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AttributeAttributeValueController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\post;

Route::get('/',  [IndexController::class, 'index'])->name('index.show');

Route::get('/category/{slug}', [CategoryController::class, 'index'])->name('category.show');

Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');

Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::get('auth/facebook', [SocialAuthController::class, 'redirectToFacebook'])->name('facebook.redirect');

Route::get('auth/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);

Route::get('auth/telegram', [SocialAuthController::class, 'redirectToTelegram'])->name('telegram.redirect');

Route::get('auth/telegram/callback', [SocialAuthController::class, 'handleTelegramCallback']);

// Route::get('/profile/view', function () {
//     return view('profile');
// })->middleware(['auth', 'verified'])->name('profile');



Route::middleware('auth')->group(function () {

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'getUserData'])->name('profile.view');
        Route::post('create/new', [ProfileController::class, 'store'])->name('profile.store');
        Route::get('create', [ProfileController::class, 'create'])->name('profile.navigate');

        Route::get('products', [ProfileController::class, 'getUserProducts'])->name('profile.products');

        Route::get('favorites', [ProfileController::class, 'getUserFavorites'])->name('profile.favorites');

        Route::get('addProduct', function () {
            $section = 'add';
            return view('products.add_product')->with('section', $section);
        })->name('profile.addProduct');

        Route::get('edit', [ProfileController::class, 'edit'])->name('profile.edit');

        Route::patch('update', [ProfileController::class, 'update'])->name('profile.update');

        Route::delete('delete', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

Route::post('/favorites/toggle', [ProfileController::class, 'toggleFavorites'])->name('favorites.toggle');

Route::middleware('auth')->group(function () {
    Route::post('/product/store', [ProductController::class, 'createProduct'])->name('products.store');
    Route::put('/product/edit', [ProductController::class, 'editProduct'])->name('products.update');
    Route::get('product/fetch/{id}', [ProductController::class, 'fetchSingleProduct'])->name('product.get.edit');
    Route::get('/fetch-attributes', [ProductController::class, 'fetchAttributesBySubcategory'])->name('fetch.attributes');
    Route::get('/product/add', [ProductController::class, 'fetchProductAttributes'])->name('product.fetch');
    Route::get('/regions/parents', [ProductController::class, 'getParentRegions']);
    Route::get('/regions/children/{parentId}', [ProductController::class, 'getChildRegions']);
});

Route::get('/obyavlenie/{slug}', [ProductController::class, 'getProduct'])->name('product.get');
Route::delete('/product/image/{id}', [ProductController::class, 'deleteImage'])->name('product.image.delete');
Route::get('/privacy-policy', function () {
    return view('privacy_policy');
});

Route::get('/pages-facebook', function () {
    return view('pages_show');
});

Route::get('/sitemap.xml', [SitemapController::class, 'generateSitemap']);

require __DIR__ . '/auth.php';

use App\Models\Product;
use TCG\Voyager\Facades\Voyager;

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();

    Route::post('attribute-attribute-values', [AttributeAttributeValueController::class, 'store'])
        ->name('voyager.attribute-attribute-values.store');
});
