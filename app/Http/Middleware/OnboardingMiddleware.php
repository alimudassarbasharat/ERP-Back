<?php

namespace App\Http\Middleware;

use App\Services\SchoolProfileService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingMiddleware
{
    protected $schoolProfileService;

    public function __construct(SchoolProfileService $schoolProfileService)
    {
        $this->schoolProfileService = $schoolProfileService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        // Skip onboarding checks for onboarding-related routes
        $allowedRoutes = [
            'api/settings/school',
            'api/settings/school/logo',
            'api/settings/school/onboarding-status',
            'api/sessions',
            'api/sessions/statistics',
            'api/logout',
            'api/user'
        ];

        // Check if current route should be allowed without onboarding
        $currentPath = trim($request->getPathInfo(), '/');
        foreach ($allowedRoutes as $allowedRoute) {
            if (str_starts_with($currentPath, trim($allowedRoute, '/'))) {
                return $next($request);
            }
        }

        // Get onboarding status
        $onboardingStatus = $this->schoolProfileService->isOnboardingComplete($user);

        // If profile is not complete, require school profile completion
        if (!$onboardingStatus['profile_complete']) {
            return response()->json([
                'success' => false,
                'message' => 'School profile must be completed before accessing this feature.',
                'redirect_to' => 'school_profile',
                'onboarding_status' => $onboardingStatus
            ], 428); // 428 Precondition Required
        }

        // If no active session exists, require session setup
        if (!$onboardingStatus['has_active_session']) {
            return response()->json([
                'success' => false,
                'message' => 'An active academic session must be set up before accessing this feature.',
                'redirect_to' => 'session_setup',
                'onboarding_status' => $onboardingStatus
            ], 428); // 428 Precondition Required
        }

        return $next($request);
    }
}