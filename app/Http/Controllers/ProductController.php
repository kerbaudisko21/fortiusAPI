<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Fetch all products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $products = Product::all();
            foreach ($products as $product) {
                $product->image = asset('images/' . $product->image);
            }
            return response()->json(['products' => $products], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching products', 'error' => $e->getMessage()], 500);
        }
    }

    public function addProduct(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric',
                'description' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images'), $imageName);
                $validatedData['image'] = $imageName;
            }

            $product = Product::create($validatedData);

            return response()->json(['message' => 'Product added successfully', 'product' => $product], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error adding product', 'error' => $e->getMessage()], 500);
        }
    }

    public function getProduct($productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                return response()->json(['product' => $product], 200);
            } else {
                return response()->json(['message' => 'Product not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching product', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProduct(Request $request, $productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                $validatedData = $request->validate([
                    'name' => 'required|string|max:255',
                    'price' => 'required|numeric',
                    'description' => 'required|string',
                    'image' => 'nullable|string', // Update the validation rule for the image field
                ]);

                $product->name = $validatedData['name'];
                $product->price = $validatedData['price'];
                $product->description = $validatedData['description'];

                if ($request->has('image')) {
                    $imageData = $request->input('image');
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $imageData = str_replace(' ', '+', $imageData);
                    $decodedImage = base64_decode($imageData);
                
                    $imageName = time() . '_' . Str::random(10) . '.png';
                    $destinationPath = public_path('images/' . $imageName);
                
                    file_put_contents($destinationPath, $decodedImage);
                
                    $product->image = $imageName;
                }

                $product->save();

                return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
            } else {
                return response()->json(['message' => 'Product not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating product', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteProduct($productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                $product->delete();
                return response()->json(['message' => 'Product deleted successfully'], 200);
            } else {
                return response()->json(['message' => 'Product not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting product', 'error' => $e->getMessage()], 500);
        }
    }
}
