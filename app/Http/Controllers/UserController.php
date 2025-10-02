<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Http\Resources\OrderCollection;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = User::with([])
                ->orderBy('created_at', 'desc');

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $perPage = $request->get('per_page', 20);
            $users = $query->paginate($perPage);

            // Retourner une ResourceCollection pour inclure pagination + meta
            // return new OrderCollection($orders);
            return Helpers::success($users);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de charger les commandes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'user_type' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'image'       => 'nullable|image|max:2048',
            ]);
            // Handle image upload if present
            $imageUrl = null;
            if ($request->hasFile('image')) {
                // Store the image in the 'products' directory
                $imagePath = $request->file('image')->store('products', 'public');
                $imageUrl = env('APP_URL') . Storage::url($imagePath); // Full URL
            }
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'user_type' => $request->user_type,
                'password' => Hash::make('123456789'),
                'image_url'    => $imageUrl,
            ]);
            return Helpers::success($user);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de charger les commandes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * @param User $user
     */
    public function show(User $user)
    {
        return Helpers::success($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
    public function dashboardServer()
    {
        return Helpers::success([
            'totalOrder'=>Order::query()->where('status','pending')->count(),
            'tableOccupied'=>Table::query()->where('status','occupied')->count(),
            'waitingPayment'=>Order::query()->where('status','completed')->count(),
            'serverName'=>Auth::user()->name
        ]);
    }
}
