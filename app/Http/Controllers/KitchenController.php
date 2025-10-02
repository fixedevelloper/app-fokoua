<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    /**
     * Liste des commandes à préparer
     */
    public function pendingOrders()
    {
        $orders = Order::with(['items.product', 'items.supplements'])
            ->whereIn('status', ['pending','preparing'])
            ->orderBy('created_at')
            ->get();

        return response()->json($orders);
    }

    /**
     * Marquer un item comme prêt / servi
     */
    public function markItemReady($orderItemId)
    {
        $item = \App\Models\OrderItem::findOrFail($orderItemId);
        $item->update(['status' => 'ready']); // champ "status" à ajouter dans migration si nécessaire

        // Si tous les items sont prêts, mettre la commande "served"
        $order = $item->order;
        if ($order->items()->where('status', '!=', 'ready')->count() == 0) {
            $order->update(['status' => 'served']);
        }

        $notification = Notification::create([
            'recipient_type' => 'server',
            'recipient_id' => $order->cashier_id,
            'order_id' => $order->id,
            'title' => 'Nouvelle commande',
            'message' => 'Une commande vient d’être assignée à vous.',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

// Broadcast immédiat
        broadcast(new NotificationEvent($notification));

        return response()->json(['message' => 'Item prêt']);
    }
}
