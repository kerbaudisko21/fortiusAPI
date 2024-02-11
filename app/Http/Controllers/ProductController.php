<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{

    /**
     * @OA\Schema(
     *     schema="Product",
     *     title="Product",
     *     required={"id", "name", "price", "description", "image"},
     *     @OA\Property(property="id", type="integer", description="Product ID"),
     *     @OA\Property(property="name", type="string", description="Product name"),
     *     @OA\Property(property="price", type="number", format="float", description="Product price"),
     *     @OA\Property(property="description", type="string", description="Product description"),
     *     @OA\Property(property="image", type="string", description="Product image URL")
     * )
     */
    private function jsonResponse($data, $message = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"product"},
     *     summary="Get List product Data",
     *     operationId="product",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/addproduct",
     *     tags={"product"},
     *     summary="Store product",
     *     description="Add a new product",
     *     operationId="addProduct",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product data",
     *         @OA\JsonContent(
     *             required={"name", "price", "description"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="image", type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/v1/product/{productId}",
     *     tags={"product"},
     *     summary="Get a specific product",
     *     operationId="getProduct",
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/v1/product/{productId}",
     *     tags={"product"},
     *     summary="Update a specific product",
     *     operationId="updateProduct",
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated product data",
     *         @OA\JsonContent(
     *             required={"name", "price", "description"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="image", type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */

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

    /**
     * @OA\Delete(
     *     path="/api/v1/product/{productId}",
     *     tags={"product"},
     *     summary="Delete a specific product",
     *     operationId="deleteProduct",
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         description="ID of the product",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */

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
