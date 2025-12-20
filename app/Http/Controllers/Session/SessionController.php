<?php

namespace App\Http\Controllers\Session;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /**
     * Display a listing of the sessions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $sessions = Session::orderBy('name', 'desc')->get();

            return response()->json([
                'success' => true,
                'result' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sessions',
                'error' => $e->getMessage()
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
            $session = Session::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 'active',
                'created_by' => Auth::id(),
                'merchant_id' => Auth::user()->merchant_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session created successfully',
                'result' => $session
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create session',
                'error' => $e->getMessage()
            ], 500);
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
                'result' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
                'error' => $e->getMessage()
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
            $session = Session::findOrFail($id);
            $session->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'updated_by' => Auth::id(),
                'merchant_id' => Auth::user()->merchant_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session updated successfully',
                'result' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified session from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $session = Session::findOrFail($request->id);

            // Check if session has any associated students or classes
            if ($session->students()->count() > 0 || $session->classes()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete session with associated students or classes'
                ], 422);
            }

            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'Session deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete session',
                'error' => $e->getMessage()
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
            $sessions = Session::select('id', 'name')
                ->where('status', 'active')
                ->orderBy('name', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'result' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 