<?php

namespace App\Http\Controllers;

use App\Events\GenericEvent;
use App\Http\Helpers\Helpers;
use App\Models\Category;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class NotificationController extends Controller
{
    /**
     * Envoyer une notification à la cuisine / caisse
     */
    public function send(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
            'event'   => 'required|string',
            'data'    => 'required|array'
        ]);

        // Broadcasting
        broadcast(new GenericEvent($request->channel, $request->event, $request->data));

        return response()->json(['message' => 'Notification envoyée']);
    }
    public function index()
    {
        $user=Auth::user();
        // Liste paginée des catégories avec leurs produits
        $notifications = Notification::where(['recipient_id'=>$user->id])->orderBy('created_at', 'asc')->get();
        return Helpers::success($notifications);
    }
    public function destroy($id)
    {
        $product = Notification::findOrFail($id);
        $product->delete();
        return Helpers::success('Notification supprimé avec succès');
    }
}
