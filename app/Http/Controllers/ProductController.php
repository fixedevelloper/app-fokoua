<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplement;
use App\Models\Accompaniment;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    /**
     * Liste des produits avec catégories, suppléments et accompagnements
     */
    public function index()
    {
        $products = Product::with(['category', 'accompaniments'])->orderBy('name', 'asc')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url,
                'is_active' => $product->is_active == 1,
                'category_id' => $product->category_id,
                'category_name' => $product->category->name,
                'type' => $product->type,
                'price' => $product->price,
                'date' => $product->created_at->toDateTimeString(),
            ];
        });
        return Helpers::success(
            $products
        );
    }

    /**
     * Affiche le formulaire de création (si API, on peut retourner les catégories disponibles)
     */
    public function create()
    {
        return response()->json([
            'categories' => Category::all(),
            'supplements' => Supplement::all(),
            'accompaniments' => Accompaniment::all(),
        ]);
    }

    /**
     * Enregistre un nouveau produit
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {

            logger($request->all());

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'preparing_time' => 'nullable|string',
                //'supplements' => 'array',
                //'accompaniments' => 'array',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
                'is_active' => 'boolean',
            ]);


            DB::transaction(function () use ($request) {
                $imageUrl = null;

                // Gérer l'upload de l'image si fourni
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $imageUrl = $file->store('products', 'public'); // stocke dans storage/app/public/products
                }
                $product = Product::create([
                    'name' => $request->name,
                    'price' => $request->price,
                    'type' => $request->type,
                    'description' => $request->description,
                    'category_id' => $request->category_id,
                    'is_active' => $request->boolean('is_active', true),
                    'image_url' => $imageUrl,
                ]);

                // Associer les suppléments (avec prix personnalisé si besoin)
                if ($request->has('supplements')) {
                    foreach ($request->supplements as $supplement) {
                        // $supplement peut contenir id + quantity ou id + custom_price
                        $product->supplements()->attach(
                            $supplement['id'],
                            ['quantity' => $supplement['quantity'] ?? 1, 'extra_price' => $supplement['extra_price'] ?? 0]
                        );
                    }
                }

                // Associer les accompagnements
                if ($request->has('accompaniments') && $request->get('type') == 'food') {
                    $accompaniments = array_map('trim', explode(',', $request->accompaniments));
                    $product->accompaniments()->attach($accompaniments);
                }
            });

            return Helpers::success('Produit créé avec succès', 'Produit créé avec succès');
        } catch (\Exception $exception) {
            return Helpers::error(['message' => 'Produit créé avec succès'], $exception->getMessage());
        }
    }

    /**
     * Affiche un produit spécifique
     */
    public function show($id)
    {
        $product = Product::with(['category', 'accompaniments'])->findOrFail($id);
        return Helpers::success([
            'id' => $product->id,
            'name' => $product->name,
            'image_url' => $product->image_url,
            'is_active' => $product->is_active == 1,
            'category_id' => $product->category_id,
            'category_name' => $product->category->name,
            'type' => $product->type,
            'price' => $product->price,
            'date' => $product->created_at->toDateTimeString(),
            'accompaniments' => $product->accompaniments
        ]);
    }

    /**
     * Met à jour un produit existant
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $product = Product::findOrFail($request->id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'supplements' => 'array',
            'accompaniments' => 'array',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'remove_image' => 'boolean' // option pour supprimer l'image existante
        ]);

        DB::transaction(function () use ($request, $product) {

            // Mettre à jour les champs de base
            $product->update($request->only(['name', 'price', 'description', 'category_id', 'is_active']));

            // Supprimer l'image existante si demandé
            if ($request->boolean('remove_image') && $product->image_url) {
                Storage::disk('public')->delete($product->image_url);
                $product->image_url = null;
            }

            // Gérer l'upload ou remplacement de l'image
            if ($request->hasFile('image')) {
                // Supprimer l'ancienne image si elle existe
                if ($product->image_url) {
                    Storage::disk('public')->delete($product->image_url);
                }
                $file = $request->file('image');
                $product->image_url = $file->store('products', 'public');
            }

            $product->save();

            // Mise à jour des suppléments
            if ($request->has('supplements')) {
                $syncData = [];
                foreach ($request->supplements as $supplement) {
                    $syncData[$supplement['id']] = [
                        'quantity' => $supplement['quantity'] ?? 1,
                        'extra_price' => $supplement['extra_price'] ?? 0
                    ];
                }
                $product->supplements()->sync($syncData);
            }

            // Mise à jour des accompagnements
            if ($request->has('accompaniments')) {
                $product->accompaniments()->sync($request->accompaniments);
            }
        });

        return Helpers::success([], 'Produit mis à jour avec succès');
    }

    public function updatePart(Request $request)
    {


        try {

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'category_id' => 'sometimes|exists:categories,id',
                'preparing_time' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
                //'remove_image'    => 'boolean' // option pour supprimer l'image existante
            ]);
            $product = Product::findOrFail($request->id);
            DB::transaction(function () use ($request, $product) {

                // Mettre à jour les champs de base
                $product->update($request->only(['name', 'price', 'description', 'category_id', 'is_active']));

                // Supprimer l'image existante si demandé
                if ($request->boolean('remove_image') && $product->image_url) {
                    Storage::disk('public')->delete($product->image_url);
                    $product->image_url = null;
                }

                // Gérer l'upload ou remplacement de l'image
                if ($request->hasFile('image')) {
                    // Supprimer l'ancienne image si elle existe
                    if ($product->image_url) {
                        Storage::disk('public')->delete($product->image_url);
                    }
                    $file = $request->file('image');
                    $product->image_url = $file->store('products', 'public');
                }

                $product->save();

                // Mise à jour des suppléments
                if ($request->has('supplements')) {
                    $syncData = [];
                    foreach ($request->supplements as $supplement) {
                        $syncData[$supplement['id']] = [
                            'quantity' => $supplement['quantity'] ?? 1,
                            'extra_price' => $supplement['extra_price'] ?? 0
                        ];
                    }
                    $product->supplements()->sync($syncData);
                }

                // Mise à jour des accompagnements
                if ($request->has('accompaniments') && $request->get('type') == 'food') {
                    $accompaniments = array_map('trim', explode(',', $request->accompaniments));
                    $product->accompaniments()->attach($accompaniments);
                }
            });
            return Helpers::success('Produit créé avec succès', 'Produit créé avec succès');
        } catch (\Exception $exception) {
            return Helpers::error(['message' => 'Produit créé avec succès'], $exception->getMessage());
        }
    }

    /**
     * Supprime un produit
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }
}
