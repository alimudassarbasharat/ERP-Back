<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolProfileService
{
    /**
     * Get merchant_id from user or admin (command loader pattern)
     * Returns string or int depending on how merchant_id is stored
     */
    private function getMerchantId($user)
    {
        if ($user instanceof User || $user instanceof Admin) {
            return $user->merchant_id;
        }
        return null;
    }

    /**
     * Get school profile for the authenticated user
     */
    public function getSchoolProfile($user): ?School
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            return null;
        }

        return School::where('merchant_id', $merchantId)->first();
    }

    /**
     * Delete school logo
     */
    public function deletelogo($user): bool
    {
        $school = $this->getSchoolProfile($user);
        
        if ($school && $school->logo) {
            Storage::disk('public')->delete($school->logo);
            $school->update(['logo' => null]);
            return true;
        }
        
        return false;
    }

    /**
     * Create or update school profile
     */
    public function createOrUpdateProfile(array $data, $user): School
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            throw new \Exception('Invalid user or merchant_id not found.');
        }

        $school = $this->getSchoolProfile($user);
        
        // Add audit fields
        $data['updated_by'] = $user->id ?? null;
        $data['merchant_id'] = $merchantId;
        
        if ($school) {
            $school->update($data);
        } else {
            $data['created_by'] = $user->id;
            $school = School::create($data);
        }
        
        // Mark as completed if profile is complete
        if ($school->isProfileComplete()) {
            $school->markAsCompleted();
        }
        
        return $school->fresh();
    }

    /**
     * Handle logo upload
     */
    public function handleLogoUpload(UploadedFile $file, $user): string
    {
        $school = $this->getSchoolProfile($user);
        
        // Delete old logo if exists
        if ($school && $school->logo) {
            Storage::disk('public')->delete($school->logo);
        }
        
        // Generate unique filename
        $fileName = 'school_logos/' . Str::random(40) . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs('', $fileName, 'public');
        
        return $path;
    }

    /**
     * Check if onboarding is complete for a user
     */
    public function isOnboardingComplete($user): array
    {
        $school = $this->getSchoolProfile($user);
        
        $profileComplete = $school && $school->isProfileComplete();
        $hasActiveSession = $school && $school->activeSession() !== null;
        
        return [
            'profile_complete' => $profileComplete,
            'has_active_session' => $hasActiveSession,
            'onboarding_complete' => $profileComplete && $hasActiveSession,
            'next_step' => $this->getNextOnboardingStep($profileComplete, $hasActiveSession),
            'school' => $school,
            'completion_percentage' => $school ? $school->getProfileCompletionPercentage() : 0
        ];
    }

    /**
     * Get the next step in onboarding
     */
    private function getNextOnboardingStep(bool $profileComplete, bool $hasActiveSession): string
    {
        if (!$profileComplete) {
            return 'school_profile';
        }
        
        if (!$hasActiveSession) {
            return 'session_setup';
        }
        
        return 'complete';
    }

    /**
     * Get school profile completion checklist
     */
    public function getProfileChecklist(School $school = null): array
    {
        if (!$school) {
            return [
                'core_identity' => false,
                'contact_info' => false,
                'address' => false,
                'branding' => false,
                'academic_defaults' => true, // These have defaults
                'communication' => false,
                'overall_complete' => false
            ];
        }
        
        return [
            'core_identity' => !empty($school->name) && !empty($school->code),
            'contact_info' => !empty($school->phone_primary) && !empty($school->email),
            'address' => !empty($school->city) && !empty($school->address_line_1),
            'branding' => !empty($school->primary_color),
            'academic_defaults' => !empty($school->timezone) && !empty($school->currency),
            'communication' => true, // Optional fields
            'overall_complete' => $school->isProfileComplete()
        ];
    }

    /**
     * Generate unique school code
     */
    public function generateSchoolCode(string $schoolName): string
    {
        $baseCode = strtoupper(Str::limit(Str::slug($schoolName, ''), 6, ''));
        $counter = 1;
        $code = $baseCode;
        
        while (School::where('code', $code)->exists()) {
            $code = $baseCode . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }
        
        return $code;
    }
}