<?php

namespace App\Http\Controllers\Session;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Models\Session;
use App\Services\SessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Display a listing of the sessions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $sessions = $this->sessionService->getSessionsForUser($user);
            $statistics = $this->sessionService->getSessionStatistics($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'sessions' => $sessions,
                    'statistics' => $statistics
                ],
                'message' => 'Sessions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to fetch sessions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get sessions list only (without statistics)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        try {
            $user = Auth::user();
            $sessions = $this->sessionService->getSessionsForUser($user);

            return response()->json([
                'success' => true,
                'data' => $sessions,
                'message' => 'Sessions list retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to fetch sessions list',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created session in storage.
     *
     * @param  \App\Http\Requests\Session\StoreSessionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSessionRequest $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'User not authenticated',
                    'error' => config('app.debug') ? 'Authentication required' : null
                ], 401);
            }
            
            $data = $request->validated();
            
            \Log::info('Creating session', [
                'user_id' => $user->id,
                'merchant_id' => $user->merchant_id,
                'data' => $data
            ]);
            
            $session = $this->sessionService->createSession($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'Session created successfully',
                'data' => $session->load(['school', 'creator'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Session creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Extract user-friendly error message
            $errorMessage = $e->getMessage() ?: 'Failed to create session';
            
            // Handle database unique constraint violations
            if (str_contains($errorMessage, 'duplicate key') || str_contains($errorMessage, 'Unique violation')) {
                if (str_contains($errorMessage, 'sessions_name_unique')) {
                    $errorMessage = 'A session with this name already exists. Please choose a different name.';
                } else {
                    $errorMessage = 'This session name is already taken. Please choose a different name.';
                }
            }
            
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        }
    }

    /**
     * Display the specified session.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $session = Session::with(['creator', 'updater'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'Session retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Session not found',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified session in storage.
     *
     * @param  \App\Http\Requests\Session\UpdateSessionRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSessionRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $session = Session::findOrFail($id);
            
            // Check ownership
            if ($session->merchant_id !== $user->merchant_id) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Unauthorized access to session'
                ], 403);
            }
            
            $data = $request->validated();
            $updatedSession = $this->sessionService->updateSession($session, $data, $user);

            return response()->json([
                'success' => true,
                'message' => 'Session updated successfully',
                'data' => $updatedSession->load(['school', 'creator', 'updater'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        }
    }

    /**
     * Remove the specified session from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $session = Session::findOrFail($id);
            
            // Check ownership
            if ($session->merchant_id !== $user->merchant_id) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Unauthorized access to session'
                ], 403);
            }

            $this->sessionService->deleteSession($session);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Session deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        }
    }

    /**
     * Activate a session
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate($id)
    {
        try {
            $user = Auth::user();
            $session = Session::findOrFail($id);
            
            // Check ownership
            if ($session->merchant_id !== $user->merchant_id) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Unauthorized access to session'
                ], 403);
            }
            
            $activatedSession = $this->sessionService->activateSession($session, $user);

            return response()->json([
                'success' => true,
                'message' => 'Session activated successfully',
                'data' => $activatedSession->load(['school', 'updater'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        }
    }

    /**
     * Archive a session
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive($id)
    {
        try {
            $user = Auth::user();
            $session = Session::findOrFail($id);
            
            // Check ownership
            if ($session->merchant_id !== $user->merchant_id) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Unauthorized access to session'
                ], 403);
            }
            
            $archivedSession = $this->sessionService->archiveSession($session, $user);

            return response()->json([
                'success' => true,
                'message' => 'Session archived successfully',
                'data' => $archivedSession->load(['school', 'updater'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to archive session',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active session for current user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSession()
    {
        try {
            $user = Auth::user();
            $session = $this->sessionService->getActiveSessionForUser($user);

            return response()->json([
                'success' => true,
                'data' => $session ? $session->load(['school']) : null,
                'message' => $session ? 'Active session retrieved' : 'No active session found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to fetch active session',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get sessions for dropdown/select options.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessionsForSelect()
    {
        try {
            $user = Auth::user();
            $sessions = $this->sessionService->getSessionsForSelect($user);

            return response()->json([
                'success' => true,
                'data' => $sessions,
                'message' => 'Sessions for select retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to fetch sessions',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * DEBUG: Get all sessions for debugging (without global scope)
     * This helps identify if sessions exist but are being filtered
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugSessions()
    {
        try {
            $user = Auth::user();
            
            // Get sessions WITHOUT global scope to see all sessions in DB
            $allSessionsInDb = \App\Models\Session::withoutGlobalScope('merchant')->get();
            
            // Get sessions WITH global scope (normal filtering)
            $filteredSessions = \App\Models\Session::all();
            
            // Get school
            $school = \App\Models\School::where('merchant_id', $user->merchant_id)->first();
            
            return response()->json([
                'success' => true,
                'debug_info' => [
                    'current_user' => [
                        'id' => $user->id,
                        'merchant_id' => $user->merchant_id
                    ],
                    'school' => [
                        'exists' => $school ? true : false,
                        'id' => $school->id ?? null,
                        'name' => $school->name ?? null
                    ],
                    'sessions_in_database' => [
                        'total_count' => $allSessionsInDb->count(),
                        'sessions' => $allSessionsInDb->map(function($s) {
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                                'merchant_id' => $s->merchant_id,
                                'school_id' => $s->school_id,
                                'status' => $s->status,
                                'is_active' => $s->is_active
                            ];
                        })
                    ],
                    'filtered_sessions' => [
                        'count' => $filteredSessions->count(),
                        'sessions' => $filteredSessions->map(function($s) {
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                                'merchant_id' => $s->merchant_id,
                                'school_id' => $s->school_id
                            ];
                        })
                    ]
                ],
                'message' => 'Debug info retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
