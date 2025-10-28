<?php

namespace App\Http\Controllers;

use App\Actions\IsSellerActivableAction;
use App\Actions\IsDeactivableSellerAction;
use App\Actions\ChangeUserStateAction;
use App\Actions\DeactivateEstablishmentOffersAction;
use App\DTOs\BasicUserDTO;
use App\Models\User;
use App\Enums\UserState;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\JsonResponse;

class AdmUserController extends Controller
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function index()
    {
        $users = $this->userRepository->getAllWithRoles();
        return response()->json($users);
    }

    public function show(string $id)
    {
        $user = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with(['roles:name'])
            ->findOrFail($id);

        $user->roles = $user->roles->pluck('name')->toArray();

        return response()->json($user);
    }

    public function activateSeller(string $id): JsonResponse
    {
        $basicUserDTO = $this->userRepository->findById($id);
        $isSellerActivableAction = new IsSellerActivableAction();
        try {
            if ($isSellerActivableAction->execute($basicUserDTO->id)) {
                $this->userRepository->changeUserState($basicUserDTO, UserState::ACTIVE);
            }
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
        return response()->json([
            'message' => 'Seller activado correctamente.',
            'user' => $basicUserDTO,
        ]);
    }

    public function deactivateSeller(string $id)
    {
        $isDeactivableSellerAction = new IsDeactivableSellerAction();
        $changeUserStateAction = new ChangeUserStateAction();
        $deactivateOffersAction = new DeactivateEstablishmentOffersAction();

        try {
            $user = $this->userRepository->findById($id);
            if ($isDeactivableSellerAction->execute($user->id)) {
                $changeUserStateAction->execute($user, UserState::INACTIVE);
                $deactivateOffersAction->executeByUserId($user->id);
            }
        }catch (Exception $exception){
            return response()->json([
                'error' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ], 500);
        }



        return response()->json([
            'message' => 'Seller desactivado correctamente.',
            'offers_deactivated' => true,
            'user' => $user
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

    public function userStats(): JsonResponse
    {
        try {
            $stats = User::select('state', \DB::raw('count(*) as count'))
                ->groupBy('state')
                ->get()
                ->map(function ($item) {
                    return [
                        'state' => $item->state,
                        'count' => $item->count
                    ];
                });

            $total = User::count();

            return response()->json([
                'message' => 'Estadísticas de usuarios obtenidas exitosamente',
                'total' => $total,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas de usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
