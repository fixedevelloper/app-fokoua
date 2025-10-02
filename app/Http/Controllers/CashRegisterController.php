<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

         // Voir l’état des caisses
    public function updateBalance(){

    }  // Ajuster manuellement le solde
 public function closeDay(){

 }       // Clôturer la caisse du jour
}
