<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserState;
use App\Services\GmailService;

class AdmUserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'last_name', 'email')
            ->with(['roles:name'])
            ->get();

        $users = $users->map(function ($user) {
            $user->roles = $user->roles->pluck('name')->toArray();
            return $user;
        });

        return response()->json($users);
    }

    public function show(string $id)
    {
        $user = User::select('id', 'name', 'last_name', 'email','state')
            ->with(['roles:name'])
            ->findOrFail($id);

        $user->roles = $user->roles->pluck('name')->toArray();

        return response()->json($user);
    }

    public function activateSeller(string $id)
    {
        $user = User::findOrFail($id);
        $gmailService = new GmailService();

        if (!$user->hasRole('seller')) {
            return response()->json([
                'message' => 'El usuario no tiene rol seller.'
            ], 422);
        }

        if ($user->state === UserState::WAITING_FOR_CONFIRMATION->value || $user->state === UserState::INACTIVE->value) {
            $user->state = UserState::ACTIVE->value;
            //$gmailService->sendEmail("lucascabjnmro2@gmail.com" , 'hola','Su establecimiento ha sido activado correctamente.');
            $user->save();
        }else {
            return response()->json([
                'message' => 'El usuario no está esperando la confirmación de su establecimiento.'
            ], 422);
        }


        return response()->json([
            'message' => 'Seller activado correctamente.',
            'user' => $user,
        ]);
    }

    public function deactivateSeller(string $id)
    {
        $user = User::findOrFail($id);

        if (!$user->hasRole('seller')) {
            return response()->json([
                'message' => 'El usuario no tiene rol seller.'
            ], 422);
        }

        if ($user->state !== UserState::ACTIVE->value) {
            return response()->json([
                'message' => 'El usuario no está activo como seller.'
            ], 422);
        }


        $user->state = UserState::INACTIVE->value;
        $user->save();


        return response()->json([
            'message' => 'Seller desactivado correctamente.',
            'user' => $user->state
        ]);
    }

    public function newSellers()
    {
        $sellers = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with(['roles:name', 'foodEstablishment:id,user_id,name,establishment_type_id', 'foodEstablishment.establishmentType:id,name'])
            ->whereHas('roles', function ($query) {
                $query->where('name', 'seller');
            })
            ->where('state', UserState::WAITING_FOR_CONFIRMATION->value)
            ->get();

        $sellers = $sellers->map(function ($seller) {
            $seller->roles = $seller->roles->pluck('name')->toArray();
            return $seller;
        });

        return response()->json($sellers);
    }

    public function showNewSeller(int $id)
    {

        $seller = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with(['roles:name', 'foodEstablishment:id,user_id,name,establishment_type_id,address', 'foodEstablishment.establishmentType:id,name'])
            ->findOrFail($id);

        $seller->roles = $seller->roles->pluck('name')->toArray();

        return response()->json($seller);
    }

    public function denySeller(string $id)
    {
        $user = User::findOrFail($id);

        if (!$user->hasRole('seller')) {
            return response()->json([
                'message' => 'El usuario no tiene rol seller.'
            ], 422);
        }

        if ($user->state !== UserState::WAITING_FOR_CONFIRMATION->value) {
            return response()->json([
                'message' => 'El usuario no está esperando la confirmación de su establecimiento.'
            ], 422);
        }

        $user->state = UserState::DENIED_CONFIRMATION;
        $user->save();

        return response()->json([
            'message' => 'Seller denegado correctamente.',
            'user' => $user,
        ]);
    }
}
