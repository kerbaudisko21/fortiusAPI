<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function jsonResponse($data, $message = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function index()
    {
        try {
            $products = Product::all();
            foreach ($products as $product) {
                $product->image = asset('images/' . $product->image);
            }
            return $this->jsonResponse(['products' => $products], 'Products fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->jsonResponse(null, 'Error fetching products', 500);
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

            return $this->jsonResponse(['product' => $product], 'Product added successfully', 201);
        } catch (\Exception $e) {
            return $this->jsonResponse(null, 'Error adding product', 500);
        }
    }

    public function getProduct($productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                return $this->jsonResponse(['product' => $product], 'Product fetched successfully', 200);
            } else {
                return $this->jsonResponse(null, 'Product not found', 404);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(null, 'Error fetching product', 500);
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
                    'image' => 'nullable|string',
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

                return $this->jsonResponse(['product' => $product], 'Product updated successfully', 200);
            } else {
                return $this->jsonResponse(null, 'Product not found', 404);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(null, 'Error updating product', 500);
        }
    }

    public function deleteProduct($productId)
    {
        try {
            $product = Product::find($productId);

            if ($product) {
                $product->delete();
                return $this->jsonResponse(null, 'Product deleted successfully', 200);
            } else {
                return $this->jsonResponse(null, 'Product not found', 404);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(null, 'Error deleting product', 500);
        }
    }
}
