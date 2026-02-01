<?php

namespace App\Http\Controllers;

use App\Actions\ChangeUserStateAction;
use App\Actions\DeactivateEstablishmentOffersAction;
use App\Actions\IsDeactivableSellerAction;
use App\Actions\IsSellerActivableAction;
use App\DTOs\BasicUserDTO;
use App\Enums\UserState;
use App\Exports\DashboardExport;
use App\Mail\SellerActivated;
use App\Mail\SellerDeactivated;
use App\Mail\SellerDenied;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class AdmUserController extends Controller
{
    public function __construct(private UserRepository $userRepository) {}

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
        $isSellerActivableAction = new IsSellerActivableAction;
        try {
            if ($isSellerActivableAction->execute($basicUserDTO->id)) {
                $updated = $this->userRepository->changeUserState($basicUserDTO, UserState::ACTIVE);
                if ($updated) {
                    // Enviar email de notificación al seller
                    Mail::to($basicUserDTO->email)->send(new SellerActivated($basicUserDTO));
                }
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
        $isDeactivableSellerAction = new IsDeactivableSellerAction;
        $changeUserStateAction = new ChangeUserStateAction;
        $deactivateOffersAction = new DeactivateEstablishmentOffersAction;

        try {
            $user = $this->userRepository->findById($id);
            if ($isDeactivableSellerAction->execute($user->id)) {
                $changeUserStateAction->execute($user, UserState::INACTIVE);
                $deactivateOffersAction->executeByUserId($user->id);
                Mail::to($user->email)->send(new SellerDeactivated($user));
            }
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
            ], 500);
        }

        return response()->json([
            'message' => 'Seller desactivado correctamente.',
            'offers_deactivated' => true,
            'user' => $user,
        ]);
    }

    public function newSellers()
    {
        $sellers = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with([
                'roles:name',
                'foodEstablishment:id,user_id,name,establishment_type_id,verification_status',
                'foodEstablishment.establishmentType:id,name',
            ])
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
            ->with([
                'roles:name',
                'foodEstablishment:id,user_id,name,establishment_type_id,address,google_place_id,google_place_data,establishment_photo,owner_selfie,phone,description,latitude,longitude,verification_status,verification_notes',
                'foodEstablishment.establishmentType:id,name',
            ])
            ->findOrFail($id);

        $seller->roles = $seller->roles->pluck('name')->toArray();

        return response()->json($seller);
    }

    public function denySeller(string $id)
    {
        $user = User::findOrFail($id);

        if (! $user->hasRole('seller')) {
            return response()->json([
                'message' => 'El usuario no tiene rol seller.',
            ], 422);
        }

        if ($user->state !== UserState::WAITING_FOR_CONFIRMATION->value) {
            return response()->json([
                'message' => 'El usuario no está esperando la confirmación de su establecimiento.',
            ], 422);
        }

        $user->state = UserState::REGISTERING;
        $user->save();
        $basicUserDTO = BasicUserDTO::fromModel($user);
        Mail::to($user->email)->send(new SellerDenied($basicUserDTO));

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
                        'count' => $item->count,
                    ];
                });

            $total = User::count();

            return response()->json([
                'message' => 'Estadísticas de usuarios obtenidas exitosamente',
                'total' => $total,
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas de usuarios',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportDashboard()
    {
        try {
            $stats = User::select('state', \DB::raw('count(*) as count'))
                ->groupBy('state')
                ->get()
                ->map(function ($item) {
                    return [
                        'state' => $item->state,
                        'count' => $item->count,
                    ];
                });

            $total = User::count();

            $userStats = [
                'total' => $total,
                'data' => $stats,
            ];

            return (new DashboardExport($userStats))->download('dashboard-stats.xlsx');

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar el dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get establishments pending verification
     */
    public function pendingEstablishments(): JsonResponse
    {
        try {
            $establishments = \App\Models\FoodEstablishment::with([
                'user:id,name,last_name,email',
                'establishmentType:id,name',
            ])
                ->where('verification_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Establecimientos pendientes obtenidos exitosamente',
                'data' => $establishments,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener establecimientos pendientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve establishment verification
     */
    public function verifyEstablishment(int $id): JsonResponse
    {
        try {
            $establishment = \App\Models\FoodEstablishment::findOrFail($id);

            if ($establishment->verification_status !== 'pending') {
                return response()->json([
                    'message' => 'El establecimiento ya ha sido verificado o rechazado',
                ], 422);
            }

            $establishment->verification_status = 'approved';
            $establishment->save();

            // Activate the seller user
            $user = $establishment->user;
            if ($user->state === UserState::WAITING_FOR_CONFIRMATION->value) {
                $user->state = UserState::ACTIVE;
                $user->save();

                // Send notification email
                Mail::to($user->email)->send(new SellerActivated(BasicUserDTO::fromModel($user)));
            }

            return response()->json([
                'message' => 'Establecimiento verificado y aprobado exitosamente',
                'establishment' => $establishment->load('user', 'establishmentType'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar el establecimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject establishment verification
     */
    public function rejectEstablishment(int $id): JsonResponse
    {
        try {
            $request = request();
            $request->validate([
                'reason' => 'required|string|min:10|max:1000',
            ]);

            $establishment = \App\Models\FoodEstablishment::findOrFail($id);

            if ($establishment->verification_status !== 'pending') {
                return response()->json([
                    'message' => 'El establecimiento ya ha sido verificado o rechazado',
                ], 422);
            }

            $establishment->verification_status = 'rejected';
            $establishment->verification_notes = $request->input('reason');
            $establishment->save();

            // Deny the seller user
            $user = $establishment->user;
            if ($user->state === UserState::WAITING_FOR_CONFIRMATION->value) {
                $user->state = UserState::REGISTERING;
                $user->save();

                // Send notification email
                Mail::to($user->email)->send(new SellerDenied(BasicUserDTO::fromModel($user)));
            }

            return response()->json([
                'message' => 'Establecimiento rechazado exitosamente',
                'establishment' => $establishment->load('user', 'establishmentType'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al rechazar el establecimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
