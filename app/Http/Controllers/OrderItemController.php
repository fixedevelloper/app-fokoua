<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Events\OrderUpdatedEvent;
use App\Http\Helpers\Helpers;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Ajouter un produit à une commande
     * @param Request $request
     * @param $orderId
     * @return JsonResponse
     */
    public function store(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0'
        ]);

        $total = $request->price * $request->quantity;

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'total' => $total
        ]);

        // Mettre à jour le total de la commande
        $order->update(['total' => $order->items()->sum('total')]);

        return response()->json(['message' => 'Article ajouté', 'item' => $item]);
    }

    /**
     * Ajouter des suppléments à un item
     * @param Request $request
     * @param $itemId
     * @return JsonResponse
     */
    public function addSupplements(Request $request, $itemId)
    {
        $item = OrderItem::findOrFail($itemId);

        $request->validate([
            'supplements' => 'required|array|min:1',
            'supplements.*.id' => 'required|exists:supplements,id',
            'supplements.*.quantity' => 'integer|min:1',
            'supplements.*.extra_price' => 'numeric|min:0'
        ]);

        DB::transaction(function () use ($item, $request) {
            foreach ($request->supplements as $sup) {
                $item->supplements()->attach(
                    $sup['id'],
                    [
                        'quantity' => $sup['quantity'] ?? 1,
                        'extra_price' => $sup['extra_price'] ?? 0
                    ]
                );

                $item->total += ($sup['quantity'] ?? 1) * ($sup['extra_price'] ?? 0);
            }

            $item->save();
            $item->order->update(['total' => $item->order->items()->sum('total')]);
        });

        return response()->json(['message' => 'Suppléments ajoutés avec succès']);
    }

    /**
     * Ajouter des accompagnements à un item
     * @param Request $request
     * @param $itemId
     * @return JsonResponse
     */
    public function addAccompaniments(Request $request, $itemId)
    {
        $item = OrderItem::findOrFail($itemId);

        $request->validate([
            'accompaniments' => 'required|array|min:1',
            'accompaniments.*' => 'exists:accompaniments,id'
        ]);

        $item->accompaniments()->sync($request->accompaniments);

        return response()->json(['message' => 'Accompagnements ajoutés avec succès']);
    }

    /**
     * Mettre à jour la quantité d’un item
     * @param Request $request
     * @param $itemId
     * @return JsonResponse
     */
    public function updateQuantity(Request $request, $itemId)
    {
        $item = OrderItem::findOrFail($itemId);

        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $item->quantity = $request->quantity;
        $item->total = ($item->price * $item->quantity) + $item->supplements()->sum(DB::raw('quantity * extra_price'));
        $item->save();

        // Mettre à jour le total de la commande
        $item->order->update(['total' => $item->order->items()->sum('total')]);

        return response()->json(['message' => 'Quantité mise à jour', 'item' => $item]);
    }

    /**
     * Met à jour le statut ou les informations d’un item commande
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $orderItem = OrderItem::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|in:pending,preparing,completed,canceled',
        ]);

        // ✅ Mise à jour du statut de l'item
        $orderItem->update($request->only(['status']));

        // ✅ Mise à jour du statut de la commande
        $order = $orderItem->order;
        $order->update(['status' => 'preparing']); // ✅ tableau associatif

        /*        $order = $orderItem->order;

                if ($order->items()->where('status', 'preparing')->exists()) {
                    $order->update(['status' => 'preparing']);
                } elseif ($order->items()->where('status', 'pending')->exists()) {
                    $order->update(['status' => 'pending']);
                } elseif ($order->items()->where('status', 'completed')->count() === $order->items()->count()) {
                    $order->update(['status' => 'completed']);
                }*/
        $notification = Notification::create([
            'recipient_type' => 'admin',
            'recipient_id' => $order->server_id,
            'order_id' => $order->id,
            'title' => 'Nouvelle commande',
            'message' => 'Une commande vient d’être assignée à vous.',
            'status' => 'sent',
            'sent_at' => now(),
        ]);


        broadcast(new NotificationEvent($notification));
        broadcast(new OrderUpdatedEvent($order->id,
            $order->status,
            'admin',
            $order->server_id
        ));
      //  event(new NotificationEvent($notification))

        return Helpers::success('message', 'Le statut a été mis à jour avec succès');
    }


    /**
     * Supprimer un item de commande
     */
    public function destroy($itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $order = $item->order;

        $item->supplements()->detach();
        $item->accompaniments()->detach();
        $item->delete();

        // Mettre à jour le total de la commande
        $order->update(['total' => $order->items()->sum('total')]);

        return response()->json(['message' => 'Article supprimé avec succès']);
    }
}
