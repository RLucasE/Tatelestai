<?php

namespace App\Http\Controllers;

use App\Actions\ChangeUserStateAction;
use App\Actions\DeactivateEstablishmentOffersAction;
use App\Actions\IsDeactivableSellerAction;
use App\Actions\IsSellerActivableAction;
use App\Actions\Verification\ApproveEstablishmentAction;
use App\Actions\Verification\RejectEstablishmentAction;
use App\Actions\Verification\ReviewVerificationFileAction;
use App\Actions\Verification\VerifyGooglePlacesProfileReviewAction;
use App\Actions\Verification\VerifyPhoneCallAction;
use App\DTOs\BasicUserDTO;
use App\Enums\TrustScoreCriterion;
use App\Enums\UserState;
use App\Events\SellerActivated;
use App\Events\SellerDeactivated;
use App\Events\SellerDenied;
use App\Exports\DashboardExport;
use App\Http\Requests\Verification\BatchReviewDocumentsRequest;
use App\Http\Requests\Verification\RejectEstablishmentRequest;
use App\Http\Requests\Verification\ReviewFileRequest;
use App\Http\Requests\Verification\VerifyGooglePlacesRequest;
use App\Http\Requests\Verification\VerifyPhoneCallRequest;
use App\Models\EstablishmentVerificationFile;
use App\Models\FoodEstablishment;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                    SellerActivated::dispatch($basicUserDTO);
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
                SellerDeactivated::dispatch($user);
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

    public function showPendingSellerDetails(int $id)
    {
        $seller = User::select('id', 'name', 'last_name', 'email', 'state')
            ->with([
                'roles:name',
                'foodEstablishment:id,user_id,name,establishment_type_id,address,google_place_id,google_place_data,phone,description,latitude,longitude,verification_status,verification_notes',
                'foodEstablishment.establishmentType:id,name',
                'foodEstablishment.verificationFiles:id,food_establishment_id,file_type',
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
        SellerDenied::dispatch($basicUserDTO);

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
     * Get establishments for verification with pagination, status filter, search and trust level
     */
    public function pendingEstablishments(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $search = trim((string) $request->query('search', ''));
            $perPage = (int) $request->query('per_page', 10);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 10;
            $shouldPaginate = $request->query('paginate', 'true') !== 'false';

            // Estadísticas globales para las tarjetas superiores y pestañas de estado
            $statsCounts = FoodEstablishment::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN verification_status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN verification_status = 'approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN verification_status = 'rejected' THEN 1 END) as rejected
                ")
                ->first();

            $stats = [
                'pending' => (int) ($statsCounts->pending ?? 0),
                'approved' => (int) ($statsCounts->approved ?? 0),
                'rejected' => (int) ($statsCounts->rejected ?? 0),
                'total' => (int) ($statsCounts->total ?? 0),
            ];

            $query = FoodEstablishment::with([
                'user:id,name,last_name,email',
                'establishmentType:id,name',
                'trustLogs',
            ])
                ->withCount([
                    'verificationFiles as pending_files_count' => function ($q) {
                        $q->where('status', 'pending');
                    },
                ])
                ->orderBy('created_at', 'desc');

            if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $query->where('verification_status', $status);
            }

            if ($search !== '') {
                $searchTerm = '%'.mb_strtolower($search, 'UTF-8').'%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$searchTerm])
                        ->orWhereHas('user', function ($uq) use ($searchTerm) {
                            $uq->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                                ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchTerm])
                                ->orWhereRaw('LOWER(email) LIKE ?', [$searchTerm]);
                        })
                        ->orWhereHas('establishmentType', function ($tq) use ($searchTerm) {
                            $tq->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                        });
                });
            }

            if ($shouldPaginate) {
                $paginator = $query->paginate($perPage);

                $items = collect($paginator->items())->map(function ($establishment) {
                    $array = $establishment->toArray();
                    $array['trust_score'] = $establishment->trust_score;
                    $array['trust_level'] = $establishment->trust_level;
                    $array['pending_files_count'] = (int) ($establishment->pending_files_count ?? 0);

                    return $array;
                });

                return response()->json([
                    'message' => 'Establecimientos obtenidos exitosamente',
                    'data' => $items,
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'stats' => $stats,
                ], 200);
            }

            $establishments = $query->get()
                ->map(function ($establishment) {
                    $array = $establishment->toArray();
                    $array['trust_score'] = $establishment->trust_score;
                    $array['trust_level'] = $establishment->trust_level;
                    $array['pending_files_count'] = (int) ($establishment->pending_files_count ?? 0);

                    return $array;
                });

            return response()->json([
                'message' => 'Establecimientos obtenidos exitosamente',
                'data' => $establishments,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $establishments->count(),
                'total' => $establishments->count(),
                'from' => $establishments->count() > 0 ? 1 : 0,
                'to' => $establishments->count(),
                'stats' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener establecimientos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single pending establishment by ID with full verification data
     */
    public function showPendingEstablishment(int $id): JsonResponse
    {
        try {
            $establishment = FoodEstablishment::with([
                'user:id,name,last_name,email,state',
                'user.roles:name',
                'establishmentType:id,name',
                'trustLogs.verifiedBy:id,name,last_name',
                'verificationFiles.reviewer:id,name,last_name',
            ])
                ->findOrFail($id);

            $establishmentArray = $establishment->toArray();
            if (isset($establishmentArray['user']['roles'])) {
                $establishmentArray['user']['roles'] = array_column($establishmentArray['user']['roles'], 'name');
            }

            $establishmentArray['trust_score'] = $establishment->trust_score;
            $establishmentArray['trust_level'] = $establishment->trust_level;

            return response()->json([
                'message' => 'Establecimiento obtenido exitosamente',
                'data' => $establishmentArray,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el establecimiento',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Review an individual verification file (accept or reject)
     */
    public function reviewFile(
        ReviewFileRequest $request,
        int $id,
        int $fileId,
        ReviewVerificationFileAction $reviewAction
    ): JsonResponse {
        try {
            $establishment = FoodEstablishment::findOrFail($id);
            $file = $establishment->verificationFiles()->findOrFail($fileId);

            $overrideCategory = $request->input('override_category')
                ? \App\Enums\TrustScoreCriterion::tryFrom($request->input('override_category'))
                : null;
            $customPoints = $request->has('points') ? (int) $request->input('points') : null;

            $reviewedFile = $reviewAction->execute(
                $file,
                request()->user(),
                $request->input('decision'),
                $request->input('rejection_reason'),
                $overrideCategory,
                $customPoints
            );

            return response()->json([
                'message' => 'Documento revisado exitosamente',
                'file' => $reviewedFile,
                'trust_score' => $establishment->fresh()->trust_score,
                'trust_level' => $establishment->fresh()->trust_level,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al revisar el archivo',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Batch review verification files (statuses and categories)
     */
    public function batchReviewDocuments(
        BatchReviewDocumentsRequest $request,
        int $id,
        ReviewVerificationFileAction $reviewAction
    ): JsonResponse {
        try {
            $establishment = FoodEstablishment::findOrFail($id);
            $user = $request->user();

            DB::transaction(function () use ($establishment, $user, $request, $reviewAction) {
                foreach ($request->input('files') as $fileData) {
                    $file = $establishment->verificationFiles()->findOrFail($fileData['id']);
                    $overrideCategory = TrustScoreCriterion::tryFrom($fileData['document_category']);
                    $status = $fileData['status'];
                    $reason = $fileData['rejection_reason'] ?? null;
                    $customPoints = isset($fileData['points']) ? (int) $fileData['points'] : null;

                    if ($status === 'accepted' || $status === 'rejected') {
                        $reviewAction->execute(
                            $file,
                            $user,
                            $status === 'accepted' ? 'accept' : 'reject',
                            $reason,
                            $overrideCategory,
                            $customPoints
                        );
                    } else {
                        // Pendiente: actualizar la categoría si cambió
                        if ($overrideCategory && $file->document_category !== $overrideCategory) {
                            $file->document_category = $overrideCategory;
                            $file->save();
                        }
                    }
                }
            });

            $freshEstablishment = $establishment->fresh(['verificationFiles', 'trustLogs']);

            return response()->json([
                'message' => 'Revisión de documentos guardada exitosamente',
                'establishment' => $freshEstablishment,
                'trust_score' => $freshEstablishment->trust_score,
                'trust_level' => $freshEstablishment->trust_level,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar la revisión de documentos',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify Google Places profile visually by admin (+20 points)
     */
    public function verifyGooglePlaces(
        VerifyGooglePlacesRequest $request,
        int $id,
        VerifyGooglePlacesProfileReviewAction $verifyAction
    ): JsonResponse {
        try {
            $establishment = FoodEstablishment::findOrFail($id);

            $updatedEstablishment = $verifyAction->execute(
                $establishment,
                $request->user(),
                $request->boolean('is_verified'),
                $request->input('notes')
            );

            return response()->json([
                'message' => 'Ficha de Google Places revisada exitosamente',
                'trust_score' => $updatedEstablishment->trust_score,
                'trust_level' => $updatedEstablishment->trust_level,
                'establishment' => $updatedEstablishment,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying Google Places profile: '.$e->getMessage(), [
                'establishment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Error al validar ficha de Google Places',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify phone call to establishment (+30 points)
     */
    public function verifyPhoneCall(
        VerifyPhoneCallRequest $request,
        int $id,
        VerifyPhoneCallAction $verifyAction
    ): JsonResponse {
        try {
            $establishment = FoodEstablishment::findOrFail($id);

            $updatedEstablishment = $verifyAction->execute(
                $establishment,
                $request->user(),
                $request->boolean('call_successful'),
                $request->input('notes')
            );

            return response()->json([
                'message' => 'Resultado de llamada telefónica registrado exitosamente',
                'trust_score' => $updatedEstablishment->trust_score,
                'trust_level' => $updatedEstablishment->trust_level,
                'establishment' => $updatedEstablishment,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying phone call: '.$e->getMessage(), [
                'establishment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Error al registrar llamada telefónica',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve establishment verification
     */
    public function verifyEstablishment(Request $request, int $id, ApproveEstablishmentAction $approveAction): JsonResponse
    {
        try {
            $establishment = FoodEstablishment::findOrFail($id);
            $notes = $request->input('notes');

            $approved = $approveAction->execute($establishment, $request->user(), $notes);

            return response()->json([
                'message' => 'Establecimiento verificado y aprobado exitosamente',
                'establishment' => $approved,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error approving establishment: '.$e->getMessage(), [
                'establishment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Error al verificar el establecimiento',
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500);
        }
    }

    /**
     * Serve verification files securely to authenticated admins
     */
    public function serveVerificationFile(int $fileId)
    {
        $file = EstablishmentVerificationFile::findOrFail($fileId);

        // Get the full path to the file in private storage
        $fullPath = storage_path('app/private/'.$file->file_path);

        // Check if file exists
        if (! file_exists($fullPath)) {
            return response()->json([
                'message' => 'Archivo no encontrado',
            ], 404);
        }

        // Get file info
        $fileInfo = pathinfo($fullPath);
        $extension = strtolower($fileInfo['extension'] ?? '');

        // Determine content type
        $contentType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream'
        };

        // Servir inline para que tanto imágenes como PDFs se visualicen directamente en una pestaña
        $disposition = 'inline';
        $filename = ($file->document_category?->value ?? $file->file_type).'.'.$extension;

        return response()->file($fullPath, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    /**
     * Reject establishment verification
     */
    public function rejectEstablishment(
        RejectEstablishmentRequest $request,
        int $id,
        RejectEstablishmentAction $rejectAction
    ): JsonResponse {
        try {
            $establishment = FoodEstablishment::findOrFail($id);

            $rejected = $rejectAction->execute($establishment, $request->user(), $request->input('reason'));

            return response()->json([
                'message' => 'Establecimiento rechazado exitosamente',
                'establishment' => $rejected,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error rejecting establishment: '.$e->getMessage(), [
                'establishment_id' => $id,
            ]);

            return response()->json([
                'message' => 'Error al rechazar el establecimiento',
                'error' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500);
        }
    }
}
