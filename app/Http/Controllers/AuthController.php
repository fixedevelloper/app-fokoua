<?php


namespace App\Http\Controllers;


use App\Http\Helpers\Helpers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * ✅ Enregistrement d'un nouvel utilisateur
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('pos-app')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token
        ], 201);
    }

    /**
     * ✅ Connexion
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required',
            'password' => 'required'
        ]);

        logger($request->all());
        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Les identifiants sont incorrects.'],
            ]);
        }

        // Supprime les anciens tokens (optionnel, pour éviter multi-sessions)
        $user->tokens()->delete();

        $token = $user->createToken('pos-app')->plainTextToken;

        return Helpers::success([
            'user_id'  => $user->id,
            'access_token' => $token,
            'user_name'=>$user->name,
            'user_role'=>$user->user_type,
            'expires_in'=>3600

        ]);
    }

    /**
     * ✅ Déconnexion
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete(); // Supprime tous les tokens
        return Helpers::success( 'Déconnecté avec succès');
    }

    /**
     * ✅ Profil de l'utilisateur connecté
     */
    public function profile(Request $request)
    {
        return Helpers::success($request->user());
    }
}

