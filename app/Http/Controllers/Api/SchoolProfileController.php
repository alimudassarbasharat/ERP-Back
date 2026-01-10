<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSchoolProfileRequest;
use App\Services\SchoolProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SchoolProfileController extends Controller
{
    protected $schoolProfileService;

    public function __construct(SchoolProfileService $schoolProfileService)
    {
        $this->schoolProfileService = $schoolProfileService;
    }

    /**
     * Get school profile
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $school = $this->schoolProfileService->getSchoolProfile($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'school' => $school,
                    'completion_percentage' => $school ? $school->getProfileCompletionPercentage() : 0,
                    'is_complete' => $school ? $school->isProfileComplete() : false,
                    'checklist' => $this->schoolProfileService->getProfileChecklist($school)
                ],
                'message' => 'School profile retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to retrieve school profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create or update school profile
     */
    public function store(UpdateSchoolProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            DB::beginTransaction();
            
            $data = $request->validated();
            
            // Handle logo upload if present
            if ($request->hasFile('logo')) {
                $data['logo'] = $this->schoolProfileService->handleLogoUpload(
                    $request->file('logo'), 
                    $user
                );
            }
            
            $school = $this->schoolProfileService->createOrUpdateProfile($data, $user);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'school' => $school,
                    'completion_percentage' => $school->getProfileCompletionPercentage(),
                    'is_complete' => $school->isProfileComplete(),
                    'checklist' => $this->schoolProfileService->getProfileChecklist($school)
                ],
                'message' => 'School profile saved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to save school profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Upload school logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $user = Auth::user();
            $logoPath = $this->schoolProfileService->handleLogoUpload(
                $request->file('logo'), 
                $user
            );

            // Update school with logo path
            $school = $this->schoolProfileService->getSchoolProfile($user);
            if ($school) {
                $school->update(['logo' => $logoPath]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'logo_path' => $logoPath,
                    'logo_url' => asset('storage/' . $logoPath)
                ],
                'message' => 'Logo uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to upload logo',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete school logo
     */
    public function deleteLogo(): JsonResponse
    {
        try {
            $user = Auth::user();
            $deleted = $this->schoolProfileService->deleteLogo($user);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => $deleted ? 'Logo deleted successfully' : 'No logo to delete'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to delete logo',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get onboarding status
     */
    public function getOnboardingStatus(): JsonResponse
    {
        try {
            $user = Auth::user();
            $status = $this->schoolProfileService->isOnboardingComplete($user);

            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'Onboarding status retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to retrieve onboarding status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Generate unique school code
     */
    public function generateCode(Request $request): JsonResponse
    {
        $request->validate([
            'school_name' => 'required|string|max:255'
        ]);

        try {
            $code = $this->schoolProfileService->generateSchoolCode($request->school_name);

            return response()->json([
                'success' => true,
                'data' => ['code' => $code],
                'message' => 'School code generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to generate school code',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get profile completion checklist
     */
    public function getChecklist(): JsonResponse
    {
        try {
            $user = Auth::user();
            $school = $this->schoolProfileService->getSchoolProfile($user);
            $checklist = $this->schoolProfileService->getProfileChecklist($school);

            return response()->json([
                'success' => true,
                'data' => $checklist,
                'message' => 'Profile checklist retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to retrieve profile checklist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark profile as completed manually
     */
    public function markAsCompleted(): JsonResponse
    {
        try {
            $user = Auth::user();
            $school = $this->schoolProfileService->getSchoolProfile($user);

            if (!$school) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'School profile not found'
                ], 404);
            }

            if (!$school->isProfileComplete()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Profile is not complete. Please fill all required fields.'
                ], 422);
            }

            $school->markAsCompleted();

            return response()->json([
                'success' => true,
                'data' => ['school' => $school->fresh()],
                'message' => 'Profile marked as completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to mark profile as completed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}