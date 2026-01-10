<?php

namespace App\Services;

use App\Models\Session;
use App\Models\School;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SessionService
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
     * Get all sessions for user's school
     * 
     * NOTE: merchant_id filtering is now handled automatically by BelongsToMerchant trait
     * No manual merchant_id filtering needed!
     */
    public function getSessionsForUser($user): Collection
    {
        $merchantId = $this->getMerchantId($user);
        
        \Log::info('SessionService: Getting sessions for user', [
            'user_id' => $user->id ?? null,
            'merchant_id' => $merchantId
        ]);
        
        if (!$merchantId) {
            \Log::warning('SessionService: No merchant_id found for user');
            return new Collection();
        }

        $school = School::where('merchant_id', $merchantId)->first();
        
        if (!$school) {
            \Log::warning('SessionService: No school found for merchant_id', ['merchant_id' => $merchantId]);
        }
        
        // If school exists, get sessions by school_id
        // merchant_id filter is automatic via trait!
        if ($school) {
            $sessions = Session::where('school_id', $school->id)
                ->with(['creator', 'updater', 'school'])
                ->orderBy('start_date', 'desc')
                ->get();
            \Log::info('SessionService: Found sessions via school_id', [
                'school_id' => $school->id,
                'session_count' => $sessions->count(),
                'sessions' => $sessions->pluck('id', 'name')
            ]);
            
            return $sessions;
        }
        
        // Fallback: Get all sessions for this merchant
        // merchant_id filter is automatic via trait!
        $sessions = Session::with(['creator', 'updater', 'school'])
            ->orderBy('start_date', 'desc')
            ->get();
            
        \Log::info('SessionService: Found sessions via merchant_id (fallback)', [
            'merchant_id' => $merchantId,
            'session_count' => $sessions->count(),
            'sessions' => $sessions->pluck('id', 'name')
        ]);
        
        return $sessions;
    }

    /**
     * Create a new session
     * 
     * NOTE: merchant_id is now auto-assigned by BelongsToMerchant trait
     */
    public function createSession(array $data, $user): Session
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            throw new \Exception('Invalid user or merchant_id not found.');
        }

        $school = School::where('merchant_id', $merchantId)->first();
        
        if (!$school) {
            throw new \Exception('School profile must be completed before creating sessions.');
        }

        // Check for overlapping sessions
        if ($this->hasOverlappingSessions($data['start_date'], $data['end_date'], $school->id)) {
            throw new \Exception('Session dates overlap with an existing session.');
        }

        $sessionData = array_merge($data, [
            'school_id' => $school->id,
            'created_by' => $user->id ?? null,
            'merchant_id' => $merchantId,  // Explicitly set (trait will use this value)
            'status' => 'draft'
        ]);

        return Session::create($sessionData);
    }

    /**
     * Update an existing session
     */
    public function updateSession(Session $session, array $data, $user): Session
    {
        // Check for overlapping sessions (excluding current session)
        if (isset($data['start_date']) && isset($data['end_date'])) {
            if ($this->hasOverlappingSessions(
                $data['start_date'], 
                $data['end_date'], 
                $session->school_id, 
                $session->id
            )) {
                throw new \Exception('Session dates overlap with an existing session.');
            }
        }

        $sessionData = array_merge($data, [
            'updated_by' => $user->id
        ]);

        $session->update($sessionData);
        return $session->fresh();
    }

    /**
     * Activate a session
     */
    public function activateSession(Session $session, $user): Session
    {
        if (!$session->hasValidDates()) {
            throw new \Exception('Session must have valid start and end dates to be activated.');
        }

        if ($session->hasOverlap()) {
            throw new \Exception('Cannot activate session with overlapping dates.');
        }

        DB::transaction(function () use ($session, $user) {
            // Deactivate other sessions for this school
            Session::where('school_id', $session->school_id)
                ->where('id', '!=', $session->id)
                ->update([
                    'is_active' => false, 
                    'status' => 'archived',
                    'updated_by' => $user->id
                ]);

            // Activate this session
            $session->update([
                'is_active' => true, 
                'status' => 'active',
                'updated_by' => $user->id
            ]);
        });

        return $session->fresh();
    }

    /**
     * Archive a session
     */
    public function archiveSession(Session $session, $user): Session
    {
        $session->update([
            'is_active' => false, 
            'status' => 'archived',
            'updated_by' => $user->id
        ]);

        return $session->fresh();
    }

    /**
     * Delete a session
     */
    public function deleteSession(Session $session): bool
    {
        if (!$session->canBeDeleted()) {
            throw new \Exception('Cannot delete session with associated students, classes, fees, or exams.');
        }

        return $session->delete();
    }

    /**
     * Get active session for user's school
     */
    public function getActiveSessionForUser($user): ?Session
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            return null;
        }

        $school = School::where('merchant_id', $merchantId)->first();
        
        if (!$school) {
            return null;
        }

        return Session::where('school_id', $school->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if dates overlap with existing sessions
     */
    private function hasOverlappingSessions(
        string $startDate, 
        string $endDate, 
        int $schoolId, 
        int $excludeSessionId = null
    ): bool {
        $query = Session::where('school_id', $schoolId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($innerQuery) use ($startDate, $endDate) {
                        $innerQuery->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        return $query->exists();
    }

    /**
     * Get sessions for select dropdown
     */
    public function getSessionsForSelect($user): Collection
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            return new Collection();
        }

        $school = School::where('merchant_id', $merchantId)->first();
        
        if (!$school) {
            return new Collection();
        }

        return Session::where('school_id', $school->id)
            ->select('id', 'name', 'start_date', 'end_date', 'is_active', 'status')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Check if user has any sessions
     */
    public function userHasSessions($user): bool
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            return false;
        }

        $school = School::where('merchant_id', $merchantId)->first();
        
        if (!$school) {
            return false;
        }

        return Session::where('school_id', $school->id)->exists();
    }

    /**
     * Get session statistics
     * 
     * NOTE: merchant_id filtering is automatic via BelongsToMerchant trait
     */
    public function getSessionStatistics($user): array
    {
        $merchantId = $this->getMerchantId($user);
        
        if (!$merchantId) {
            return [
                'total_sessions' => 0,
                'active_sessions' => 0,
                'draft_sessions' => 0,
                'archived_sessions' => 0
            ];
        }

        $school = School::where('merchant_id', $merchantId)->first();
        
        // Get all sessions for this merchant
        // merchant_id filter is automatic via trait!
        if ($school) {
            $sessions = Session::where('school_id', $school->id)->get();
        } else {
            $sessions = Session::all(); // Already filtered by merchant_id via trait!
        }

        return [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('is_active', true)->count(),
            'draft_sessions' => $sessions->where('status', 'draft')->count(),
            'archived_sessions' => $sessions->where('status', 'archived')->count()
        ];
    }
}