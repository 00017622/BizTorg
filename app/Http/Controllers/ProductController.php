<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;

use App\Models\Category;
use App\Services\TelegramService;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Region;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\FacebookService;
use App\Services\InstagramService;
use TCG\Voyager\Commands\InstallCommand;

class ProductController extends Controller
{
    protected $telegramService;
    protected $facebookService;
    protected $instagramService;

    public function __construct(TelegramService $telegramService, FacebookService $facebookService, InstagramService $instagramService)
    {
        $this->telegramService = $telegramService;
        $this->facebookService = $facebookService;
        $this->instagramService = $instagramService;
    }

    public function fetchProductAttributes() {
        $categories = Category::get();
        $subcategories = Subcategory::get();
        $section = 'add';
        return view('products.add_product', compact('categories', 'section', 'subcategories'));
    }

    public function fetchAttributesBySubcategory(Request $request)
{
    $subcategoryId = $request->input('subcategory_id');
    
    if (!$subcategoryId) {
        return response()->json(['error' => 'Subcategory ID is required'], 400);
    }

    $subcategory = Subcategory::with('attributes.attributeValues')->find($subcategoryId);

    if (!$subcategory) {
        return response()->json(['error' => 'Subcategory not found'], 404);
    }

    $attributes = $subcategory->attributes->map(function ($attribute) {
        return [
            'id' => $attribute->id,
            'name'=> $attribute->name,
            'values' => $attribute->attributeValues->map(function ($value) {
                return [
                    'id' => $value->id,
                    'name' => $value->value,
                ];
            })
        ];
    });

    return response()->json($attributes);
}

public function createProduct(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'required|string|max:900',
        'subcategory_id' => 'required|exists:subcategories,id',
        'image1' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        'image2' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        'image3' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        'image4' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'attributes' => 'required|array',
        'attributes.*' => 'integer|exists:attribute_values,id',
        'price' => 'required|numeric|min:0',
        'currency' => 'required|string|in:сум,доллар',
        'type' => 'required|string|in:sale,purchase',
        'child_region_id' => 'required|exists:regions,id',
    ]);

    $slug = Str::slug($validatedData['name'], '-');

    try {
        DB::transaction(function () use ($validatedData, $request, $slug) {
            $product = Product::create([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'subcategory_id' => $validatedData['subcategory_id'],
                'description' => $validatedData['description'],
                'price' => $validatedData['price'],
                'currency' => $validatedData['currency'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'type' => $validatedData['type'],
                'region_id' => $validatedData['child_region_id'],
                'user_id' => $request->user()->id,
            ]);

            foreach (['image1', 'image2', 'image3', 'image4'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    try {
                        $path = $request->file($imageField)->store('product-images', 'public');
                        Log::info("Image uploaded successfully to: $path");
            
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $path,
                        ]);
                    } catch (\Exception $e) {
                        Log::error("Failed to upload image: " . $e->getMessage());
                    }
                } else {
                    Log::warning("No file detected for: $imageField");
                }
            }
            

            $product->attributeValues()->sync($validatedData['attributes']);

            $productInfo = <<<INFO
📢 <b>Объявление:</b> {$product->name}

📝 <b>Описание:</b> {$product->description}

📍 <b>Регион:</b> {$product->region->parent->name}, {$product->region->name}

👤 <b>Контактное лицо:</b> {$product->user->name}

📞 <b>Номер телефона:</b> <a href="tel:{$product->user->profile->phone}">{$product->user->profile->phone}</a>

🌍 <b>Карта:</b> <a href="https://www.google.com/maps?q={$product->latitude},{$product->longitude}">Местоположение в Google Maps</a>

🌍 <b>Карта:</b> <a href="https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm">Местоположение в Yandex Maps</a>


🔗 <a href="https://biztorg.uz/obyavlenie/{$product->slug}">Подробнее по ссылке</a>
INFO;


               $images = ProductImage::where('product_id', $product->id)->pluck('image_url')->map(function ($path) {
             
                   $url = asset("storage/{$path}");
                   Log::info("Constructed image URL: {$url}");
                   return $url;
               })->toArray();
               

        if (!is_array($images)) {
            throw new \InvalidArgumentException('Images should be an array.');
        }

        try {
            if (count($images) > 1) {
                $media = array_map(function ($image, $index) use ($productInfo) {
                    $mediaItem = [
                        'type' => 'photo',
                        'media' => $image,
                        'parse_mode' => 'HTML',
                    ];
                    if ($index === 0) {
                        $mediaItem['caption'] = $productInfo;
                    }
                    return $mediaItem;
                }, $images, array_keys($images));
                
                $this->telegramService->sendMediaGroup($media);
            } elseif (count($images) === 1) {
                Log::info("Sending single photo to Telegram: " . $images[0]);
                $this->telegramService->sendPhoto($images[0], $productInfo);
            } else {
                $this->telegramService->sendMessage($productInfo);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram message: " . $e->getMessage());
        }
        
        try {

            $facebookProductInfo = <<<INFO
📢 Объявление: {$product->name}

📝 Описание: {$product->description}

📍 Регион: {$product->region->parent->name}, {$product->region->name}

👤 Контактное лицо: {$product->user->name}

📞 Номер телефона: {$product->user->profile->phone}

🌍 Карта: Местоположение в Google Maps: https://www.google.com/maps?q={$product->latitude},{$product->longitude}

🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm

🔗 Подробнее по ссылке: https://biztorg.uz/obyavlenie/{$product->slug}
INFO;

        $imagesForFacebook = ProductImage::where('product_id', $product->id)->get()->map(function ($image) {
            $path = str_replace('\\', '/', $image->image_url);
            return [
                'id' => $image->id,
                'image_url' => asset("storage/{$path}"), 
            ];
        })->toArray();

        
        
        $this->facebookService->createPost($facebookProductInfo, $imagesForFacebook);

            
        } catch (\Exception $e) {
            Log::error("Failed to send Facebook post" . $e->getMessage());
        }

        try {
        
            $productImagesUrls = ProductImage::where('product_id', $product->id)->pluck('image_url');
            $imagesUrls = [];

            foreach ($productImagesUrls as $productImageUrl) {
                $imagesUrls[] = asset("storage/{$productImageUrl}");
            }
        
            $region = $product->region->parent->name ?? 'Unknown Region';
            $subregion = $product->region->name ?? 'Unknown Subregion';
            $phone = $product->user->profile->phone ?? 'No Phone Number Provided';
        
            $instaMessage = "
            📢 Объявление: {$product->name}
        
            📝 Описание: {$product->description}
        
            📍 Регион: {$region}, {$subregion}
        
            👤 Контактное лицо: {$product->user->name}
        
            📞 Номер телефона: {$phone}
        
            🌍 Карта: Местоположение в Google Maps: https://www.google.com/maps?q={$product->latitude},{$product->longitude}
        
            🌍 Карта: Местоположение в Yandex Maps: https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm
        
            🔗 Подробнее по ссылке: https://biztorg/obyavlenie/{$product->slug}
            ";
        
            $this->instagramService->createCarouselPost($instaMessage, $imagesUrls);
        
        } catch (\Exception $e) {
            Log::error("Failed to send Instagram post: " . $e->getMessage());
        }
        
        
       
    });
        return redirect(route('profile.products'))->with('success', 'Product created successfully!');
    } catch (\Exception $e) {
        Log::error('Product creation failed: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
    }
}



public function getParentRegions()
{
    $parentRegions = Region::whereNull('parent_id')->get();
    return response()->json($parentRegions);
}

public function getChildRegions($parentId)
{
    $childRegions = Region::where('parent_id', $parentId)->get();
    return response()->json($childRegions);
}

public function getProduct($slug) {
    $product = Product::where('slug', $slug)->firstOrFail();
    $user = $product->user;

    $attributes = $product->subcategory->attributes()->with(['attributeValues' => function ($query) use ($product) {
        $query->whereExists(function ($q) use ($product) {
            $q->from('product_attribute_values')
              ->whereColumn('product_attribute_values.attribute_value_id', 'attribute_values.id')
              ->where('product_attribute_values.product_id', $product->id);
        });
    }])->get();

    $userProducts = $user->products()->where('id', '!=', $product->id)->latest()->limit(10)->get();
    $sameProducts = Product::where('subcategory_id', $product->subcategory->id)->where('id', '!=', $product->id)->whereNotIn('id', $user->products->pluck('id')->toArray())->latest()->limit(10)->get();

    return view('products.product_detail', compact('product', 'sameProducts', 'user', 'attributes', 'userProducts'));
}

public function fetchSingleProduct($id) {
    $product = Product::with('attributes.attributeValues')->findOrFail($id);

    $categories = Category::get();
    $subcategories = Subcategory::get();
    $section = 'product_update';

    $productImages = $product->images->map(function ($image) {
        return ['image_url' => $image->image_url];
    });

    
    $attributes = $product->subcategory->attributes()->with([
        'attributeValues' => function ($query) use ($product) {
            $query->whereExists(function ($q) use ($product) {
                $q->from('product_attribute_values')
                  ->whereColumn('product_attribute_values.attribute_value_id', 'attribute_values.id')
                  ->where('product_attribute_values.product_id', $product->id);
            });
        }
    ])->get();
    
    $productAttributes = $attributes->mapWithKeys(function ($attribute) {
        $selectedValue = $attribute->attributeValues->first();
        return [
            $attribute->id => [
                'id' => $selectedValue->id ?? null,
                'name' => $selectedValue->value ?? 'No value assigned', 
            ],
        ];
    });

    return view('products.edit_product', compact('product', 'productAttributes', 'productImages', 'categories', 'subcategories', 'section'));
}


public function editProduct(Request $request) {
   

    try {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:900',
            'subcategory_id' => 'required|exists:subcategories,id',
            'image1' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'image2' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'image3' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'image4' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'attributes' => 'required|array',
            'attributes.*' => 'integer|exists:attribute_values,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:сум,доллар',
            'type' => 'required|string|in:sale,purchase',
            'child_region_id' => 'required|exists:regions,id',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed:', $e->errors());
        return redirect()->back()->withErrors($e->errors());
    }

    $slug = Str::slug($validatedData['name'], '-') . '-' . $request->input('product_id');

    try {
        DB::transaction(function () use ($validatedData, $request, $slug) {
            $product = Product::findOrFail($request->input('product_id'));

            $product->update([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'subcategory_id' => $validatedData['subcategory_id'],
                'description' => $validatedData['description'],
                'price' => $validatedData['price'],
                'currency' => $validatedData['currency'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'type' => $validatedData['type'],
                'region_id' => $validatedData['child_region_id'],
            ]);

            foreach (['image1', 'image2', 'image3', 'image4'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    $path = $request->file($imageField)->store('product-images', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $path,
                    ]);
                }
            }

            $product->attributeValues()->sync($validatedData['attributes']);
        });

        return redirect(route('profile.products'))->with('success', 'Product updated successfully!');
    } catch (\Exception $e) {
        Log::error('Product editing failed: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while creating the product. Please try again.');
    }

}

public function deleteImage($id)
{
    Log::info('Attempting to delete image with ID: ' . $id);
    

    try {
        Log::info('Deleting image with ID: ' . $id);

        $image = ProductImage::findOrFail($id);

        if (Storage::exists('public/' . $image->image_url)) {
            Storage::delete('public/' . $image->image_url);
        }

        $image->delete();

        return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
    } catch (\Exception $e) {
        Log::error('Error deleting image: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to delete image.'], 500);
    }
}

}


