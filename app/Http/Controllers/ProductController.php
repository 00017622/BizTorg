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
    // Validation
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
            // Create the product
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

            // Save product images
            $imageUrls = [];
            foreach (['image1', 'image2', 'image3', 'image4'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    // Store the image in the "public" disk
                    $path = $request->file($imageField)->store('product-images', 'public');
                    $publicUrl = asset("storage/$path"); // Publicly accessible URL for Puppeteer
                    $imageUrls[] = $publicUrl;

                    // Save image info to the database
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $path,
                    ]);
                }
            }

            // Execute Puppeteer Script
            if (!empty($imageUrls)) {
                $scriptPath = base_path('scripts/postToSocial.js');
                $escapedImageUrls = implode(' ', array_map('escapeshellarg', $imageUrls));

                $command = sprintf(
                    'node %s %s %s %s',
                    escapeshellarg($scriptPath),
                    escapeshellarg($product->name),
                    escapeshellarg($product->description),
                    $escapedImageUrls
                );

                Log::info("Executing Puppeteer script: $command");

                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    Log::error('Failed to execute Puppeteer script: ' . implode("\n", $output));
                } else {
                    Log::info('Puppeteer script executed successfully: ' . implode("\n", $output));
                }
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
    
    // Map the attributes to the required structure
    $productAttributes = $attributes->mapWithKeys(function ($attribute) {
        $selectedValue = $attribute->attributeValues->first(); // Get the selected value (if any)
        return [
            $attribute->id => [
                'id' => $selectedValue->id ?? null, // ID of the selected value
                'name' => $selectedValue->value ?? 'No value assigned', // Name of the selected value
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


        // Delete the file from storage
        if (Storage::exists('public/' . $image->image_url)) {
            Storage::delete('public/' . $image->image_url);
        }

        // Remove the image record from the database
        $image->delete();

        return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
    } catch (\Exception $e) {
        Log::error('Error deleting image: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Failed to delete image.'], 500);
    }
}


}


