<?php

namespace App\Http\Controllers;

use App\Events\GenericEvent;
use App\Events\OrderCreated;
use App\Http\Helpers\Helpers;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Affiche la liste des commandes
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['table', 'items.product', 'items.supplements'])
                ->orderBy('created_at', 'desc');

            // Filtre par statut
            if ($request->has('status') && strtolower($request->status) !== 'all') {
                $query->where('status',strtolower($request->status));
            }

            // Filtre par date
            if ($request->has('date') && $request->date != '') {
                switch (strtolower($request->date)) {
                    case 'today':
                        $query->whereDate('created_at', now()->toDateString());
                        break;
                    case 'yesterday':
                        $query->whereDate('created_at', now()->subDay()->toDateString());
                        break;
                    default:
                        $query->whereDate('created_at', $request->date);
                }
            }

            // Filtre par recherche
            if ($request->has('search') && $request->search != '') {
                $query->where(function($q) use ($request) {
                    $q->where('order_number', 'like', "%{$request->search}%")
                        ->orWhereHas('table', fn($q2) => $q2->where('name', 'like', "%{$request->search}%"));
                });
            }

            $perPage = (int) $request->get('per_page', 20);
            $orders = $query->paginate($perPage)->appends($request->query());

            return Helpers::success(new OrderCollection($orders));

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de charger les commandes.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }



    /**
     * Créer une nouvelle commande
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return Helpers::error( 'Unauthenticated');
            }
            $validated = $request->validate([
                'table_id'            => 'nullable|exists:tables,id',
                // 'order_type'       => 'required|in:dine_in,takeaway,delivery',
                'items'               => 'required|array|min:1',
                'items.*.product_id'  => 'required|exists:products,id',
                'items.*.quantity'    => 'required|integer|min:1',
                'items.*.type'        => 'required|string',
                'items.*.price'       => 'required|numeric|min:0',
                'items.*.supplements' => 'array' // [{id, quantity, extra_price}]
            ]);

            DB::transaction(function () use ($validated) {

                $order = Order::create([
                    'table_id'  => $validated['table_id'],
                    'order_type'=> $validated['order_type'] ?? null,
                    'status'    => 'pending', // pending, preparing, served, paid, canceled
                    'total'     => 0,         // sera recalculé
                    'preparing_time' => 0,
                    'server_id' => \auth()->user()->id
                ]);

                // Totaux
                $total       = 0;
                $totalFood   = 0;
                $totalDrink  = 0;
                $preparingTime = 0;
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $quantity  = $item['quantity'];
                    $basePrice = $item['price'];
                    $lineTotal = $basePrice * $quantity;

                    $orderItem = OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $quantity,
                        'base_price' => $basePrice,
                        'type'       => $item['type'],
                        'total'      => $lineTotal
                    ]);
                    // 🔹 Calcul du temps de préparation
                    if ($item['type'] === 'food') {
                        // On additionne le temps de chaque plat × quantité
                        $preparingTime += ($product->preparing_time ?? 0) * $item['quantity'];
                    }
                    // Suppléments
                    if (!empty($item['supplements'])) {
                        foreach ($item['supplements'] as $supplement) {
                            $extraPrice = $supplement['extra_price'] ?? 0;
                            $suppQty    = $supplement['quantity'] ?? 1;

                            $orderItem->supplements()->attach(
                                $supplement['id'],
                                [
                                    'quantity'    => $suppQty,
                                    'extra_price' => $extraPrice
                                ]
                            );

                            $lineTotal += $extraPrice * $suppQty;
                        }

                        // Mise à jour du total de la ligne après suppléments
                        $orderItem->update(['total' => $lineTotal]);
                    }

                    // Mise à jour des totaux
                    $total += $lineTotal;
                    if ($item['type'] === 'drink') {
                        $totalDrink += $lineTotal;
                    } else {
                        $totalFood += $lineTotal;
                    }
                }

                // Mise à jour finale de la commande
                $order->update([
                    'grand_total' => $total,
                    'total_food'  => $totalFood,
                    'total_drink' => $totalDrink,
                    'preparing_time' => $preparingTime
                ]);
                $order->table()->update([
                   'status'=>'occupied'
                ]);
/*                broadcast(new GenericEvent('order.updated', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                ]));*/
                // 🔹 Récupère la commande avec ses relations
                $orderModel = Order::with(['table', 'items.product', 'items.supplements'])
                    ->firstWhere('id', $order->id);

// 🔹 Crée la resource
                $orderResource = new OrderResource($orderModel);

// 🔹 Broadcast sur Reverb / WebSocket
                broadcast(new OrderCreated($orderResource->toArray(null), $order->server_id));

            });

            return Helpers::success('Commande créée avec succès');
        }
        catch (\Exception $e) {
            return Helpers::error($e->getMessage());
        }
    }


    /**
     * Affiche une commande spécifique
     */
    public function show($id)
    {
        $order = Order::with(['table', 'items.product', 'items.supplements'])->findOrFail($id);
        return Helpers::success(new OrderResource($order));
    }

    /**
     * Met à jour le statut ou les informations d’une commande
     */
    public function update(Request $request, $id)
    {
        try {

        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|in:pending,preparing,served,paid,canceled',
            'table_id' => 'sometimes|exists:tables,id'
        ]);

        $order->update($request->only(['status', 'table_id']));
        broadcast(new GenericEvent('order.updated', [
            'order_id' => $order->id,
            'status' => $order->status,
        ]));
        return response()->json(['message' => 'Commande mise à jour avec succès']);
        }
        catch (\Exception $e) {
            return Helpers::error($e->getMessage());
        }
    }

    /**
     * Supprime une commande (si elle n'est pas déjà payée)
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'paid') {
            return response()->json(['message' => 'Impossible de supprimer une commande déjà payée'], 409);
        }

        $order->delete();

        return response()->json(['message' => 'Commande supprimée avec succès']);
    }
}
