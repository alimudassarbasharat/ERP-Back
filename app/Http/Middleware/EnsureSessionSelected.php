<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Session;
use Illuminate\Support\Facades\Auth;

/**
 * Ensure Session Selected Middleware
 * ===================================
 * 
 * Backend enforcement of session requirement for academic modules.
 * 
 * Features:
 * - Checks if user's merchant has an active session
 * - Returns proper JSON error if session missing
 * - Prevents backend bypass of frontend validation
 * - Multi-tenant safe (merchant_id scoped)
 * 
 * Usage in routes/api.php:
 * ```php
 * Route::middleware(['auth:api', 'session.required'])->group(function () {
 *     Route::get('/students', [StudentController::class, 'index']);
 *     Route::get('/exams', [ExamController::class, 'index']);
 * });
 * ```
 */
class EnsureSessionSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'User not authenticated'
            ], 401);
        }

        // Get merchant's school
        $school = School::where('merchant_id', $user->merchant_id)->first();
        
        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'School profile must be completed first',
                'error' => 'No school found for this merchant',
                'redirect' => '/settings/school-profile'
            ], 400);
        }

        // Check if active session exists for this school
        $activeSession = Session::where('school_id', $school->id)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Please add or select an academic session first',
                'error' => 'No active session found for this school',
                'redirect' => '/settings/sessions',
                'action_required' => 'create_or_activate_session'
            ], 400);
        }

        // Attach session to request for controllers to use
        $request->merge(['current_session_id' => $activeSession->id]);
        $request->attributes->set('current_session', $activeSession);

        return $next($request);
    }
}
