<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Models\Accompaniment;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccompanimentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        // Liste paginée des catégories avec leurs produits
        $categories = Accompaniment::query()->orderBy('name', 'asc')->get();
        return Helpers::success($categories);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'name' => 'required|string|max:255',
                'max_free' => 'nullable|integer',
            ]);

            $category = Accompaniment::create([
                'name' => $request->name,
                'max_free' => $request->max_free,
            ]);

            return Helpers::success([
                'message' => 'Catégorie créée avec succès',
                'category' => $category
            ], 201);
        }catch (\Exception $exception){
            return Helpers::error($exception->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * @param Accompaniment $accompaniment
     * @return JsonResponse
     */
    public function show(Accompaniment $accompaniment)
    {
        return Helpers::success($accompaniment);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Accompaniment $accompaniment
     * @return JsonResponse
     */
    public function update(Request $request, Accompaniment $accompaniment)
    {
        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'max_free' => 'nullable|integer',

        ]);

        $accompaniment->update($request->only(['name', 'max_free']));

        return Helpers::success(['accompaniment' => $accompaniment], 'Catégorie mise à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accompaniment $accompaniment)
    {
        //
    }
}
