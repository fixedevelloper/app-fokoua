<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Helpers;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Ajouter un paiement à une commande
     * @param Request $request
     * @param $orderId
     * @return JsonResponse
     */
    public function store(Request $request, $orderId)
    {
        try {


        $order = Order::findOrFail($orderId);

        $request->validate([
            'moyen' => 'required|in:Cash,Card,Mobile',
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($order, $request) {

            // ✅ Enregistrer ce paiement (historique)
            Payment::create([
                'order_id' => $order->id,
                'method'   => $request->moyen,
                'amount'   => $request->amount,
            ]);

            // ✅ Calculer le total déjà payé
            $totalPaid = $order->payments()->sum('amount');

            if ($totalPaid >= $order->grand_total) {
                // ✅ La commande est totalement payée
                $order->update(['status_payment' => 'paid']);

                // ➡️ Regrouper les paiements par caisse
                $byCash = $order->payments()
                    ->select('method', DB::raw('SUM(amount) as total'))
                    ->groupBy('method')
                    ->get();

                // ➡️ Mettre à jour chaque caisse
                foreach ($byCash as $row) {
                    $cash = CashRegister::firstOrCreate(
                        ['type' => $row->method],
                        ['balance' => 0]
                    );
                    $cash->increment('balance', $row->total);
                }

            } else {
                // ✅ Paiement partiel, on change juste le statut
                $order->update(['status_payment' => 'partial']);
            }
            $order->table()->update([
                'status'=>'free'
            ]);
        });

        return Helpers::success([
            'message' => 'Paiement enregistré',
            'status'  => $order->fresh()->status_payment,
            'total_paid' => $order->payments()->sum('amount'),
            'remaining' => max($order->total - $order->payments()->sum('amount'), 0)
        ]);
        }catch (\Exception $exception){
            return Helpers::error([
                'message' => $exception->getMessage(),
             ]);
        }

    }


    /**
     * Liste des paiements d'une commande
     * @param Request $request
     * @param $orderId
     * @return JsonResponse
     */
    public function paymentByOrder(Request $request,$orderId)
    {
        $perPage = $request->get('per_page', 20);
        $payments = Payment::where('order_id', $orderId)->paginate($perPage);

        return Helpers::success(['data'=>PaymentResource::collection($payments)]);
    }
    /**
     * Liste des paiements d'une commande
     * @param Request $request
     * @param $orderId
     * @return JsonResponse
     */
    public function paymentStat(Request $request)
    {

        try {
            $start = $request->get('start_date');
            $end   = $request->get('end_date');

            $query = Payment::query();

            // ✅ Filtrer par période
            if ($start && $end) {
                $query->whereBetween('created_at', [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay()
                ]);
            }

            // ✅ Regrouper par moyen de paiement
            $data = $query->selectRaw('method, SUM(amount) as total')
                ->groupBy('method')
                ->get();

            return Helpers::success($data);

        } catch (\Exception $e) {
            return Helpers::error( 'Impossible de charger les rapports.',[
                'status'  => 'error',
                'message' => 'Impossible de charger les rapports.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ]);
        }


    }
    /**
     * Liste des paiements d'une commande
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $payments = Payment::with(['order'])
            ->orderBy('created_at', 'desc')->paginate($perPage);
        return Helpers::success(['data'=>PaymentResource::collection($payments)]);
    }
}
