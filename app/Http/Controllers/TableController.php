<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Affiche la liste des tables
     */
    public function index()
    {
        $tables = Table::withCount('orders')->orderBy('number')->get();
        return Helpers::success(
            $tables);
    }

    /**
     * Crée une nouvelle table
     */
    public function store(Request $request)
    {
        $request->validate([
            'number'   => 'required|integer|unique:tables,number',
            'capacity' => 'required|integer|min:1',
            'status'   => 'sometimes|in:free,occupied,reserved'
        ]);

        $table = Table::create([
            'number'   => $request->number,
            'capacity' => $request->capacity,
            'status'   => $request->status ?? 'free',
        ]);

        return response()->json([
            'message' => 'Table créée avec succès',
            'table'   => $table
        ], 201);
    }

    /**
     * Affiche une table spécifique
     */
    public function show($id)
    {
        $table = Table::with('orders')->findOrFail($id);
        return response()->json($table);
    }

    /**
     * Met à jour une table (capacité, statut)
     */
    public function update(Request $request, $id)
    {
        $table = Table::findOrFail($id);

        $request->validate([
            'number'   => 'sometimes|integer|unique:tables,number,' . $table->id,
            'capacity' => 'sometimes|integer|min:1',
            'status'   => 'sometimes|in:free,occupied,reserved'
        ]);

        $table->update($request->only(['number','capacity','status']));

        return response()->json([
            'message' => 'Table mise à jour avec succès',
            'table'   => $table
        ]);
    }

    /**
     * Supprime une table (si aucune commande active)
     */
    public function destroy($id)
    {
        $table = Table::findOrFail($id);

        if ($table->orders()->whereIn('status', ['pending','preparing','served'])->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : des commandes sont encore actives sur cette table.'
            ], 409);
        }

        $table->delete();

        return response()->json(['message' => 'Table supprimée avec succès']);
    }

    /**
     * Changer le statut d'une table (free / occupied / reserved)
     */
    public function updateStatus(Request $request, $id)
    {
        $table = Table::findOrFail($id);

        $request->validate([
            'status' => 'required|in:free,occupied,reserved'
        ]);

        $table->update(['status' => $request->status]);

        return response()->json(['message' => 'Statut de la table mis à jour', 'table' => $table]);
    }
}
