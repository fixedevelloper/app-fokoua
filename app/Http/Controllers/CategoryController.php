<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Affiche la liste des catégories
     */
    public function index()
    {
        // Liste paginée des catégories avec leurs produits
        $categories = Category::with('products')->orderBy('name', 'asc')->get();
        return Helpers::success($categories);
    }

    /**
     * Affiche une catégorie spécifique
     */
    public function show($id)
    {
        $category = Category::with('products')->findOrFail($id);
        return Helpers::success($category);
    }

    /**
     * Crée une nouvelle catégorie
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'type'        => 'required|in:food,drink,dessert,other', // Ex: type pour différencier nourriture/boisson
            'is_active'   => 'boolean'
        ]);

        $category = Category::create([
            'name'        => $request->name,
            'description' => $request->description,
            'type'        => $request->type,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return Helpers::success([
            'message'  => 'Catégorie créée avec succès',
            'category' => $category
        ], 201);
    }

    /**
     * Met à jour une catégorie
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'type'        => 'sometimes|in:food,drink,dessert,other',
            'is_active'   => 'boolean'
        ]);

        $category->update($request->only(['name', 'description', 'type', 'is_active']));

        return Helpers::success([
            'message'  => 'Catégorie mise à jour avec succès',
            'category' => $category
        ]);
    }

    /**
     * Supprime une catégorie
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Optionnel : empêcher la suppression si des produits sont liés
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : des produits sont encore liés à cette catégorie.'
            ], 409);
        }

        $category->delete();

        return Helpers::success(['message' => 'Catégorie supprimée avec succès']);
    }
}
