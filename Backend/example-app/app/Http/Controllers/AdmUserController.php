<?php

namespace App\Http\Controllers;

use App\Models\User;

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
        $user = User::select('id', 'name', 'last_name', 'email')
            ->with(['roles:name'])
            ->findOrFail($id);

        $user->roles = $user->roles->pluck('name')->toArray();

        return response()->json($user);
    }
}
