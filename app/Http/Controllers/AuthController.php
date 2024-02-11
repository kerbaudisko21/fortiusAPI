<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="User",
     *     title="User",
     *     required={"id", "username", "email", "password"},
     *     @OA\Property(property="id", type="integer", description="User ID"),
     *     @OA\Property(property="username", type="string", description="Username"),
     *     @OA\Property(property="email", type="string", format="email", description="Email address"),
     *     @OA\Property(property="password", type="string", format="password", description="Password"),
     *     @OA\Property(property="role", type="string", description="User role")
     * )
     */

    private function jsonResponse($data = null, $message = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     operationId="register",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration details",
     *         @OA\JsonContent(
     *             required={"username", "email", "password"},
     *             @OA\Property(property="username", type="string", example="wilbert"),
     *             @OA\Property(property="email", type="string", format="email", example="wilbert@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="wilbert123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return $this->jsonResponse(['user' => $user], 'User registered successfully', 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Authenticate user",
     *     operationId="login",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="wilbert@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="wilbert123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="role", type="string", example="admin"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $role = $user->role;

            $accessToken = $user->createToken('authToken')->accessToken;
            return $this->jsonResponse(['user' => $user, 'role' => $role, 'access_token' => $accessToken], 'Login successful');
        } else {
            return $this->jsonResponse(null, 'Unauthorized', 401);
        }
    }
}
