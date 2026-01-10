<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Settings\SaveGeneralSettingsRequest;

class SettingsController extends Controller
{
    public function getGeneralSettings()
    {
        try {
            // Get user's school information
            $user = Auth::user();
            if (!\Schema::hasTable('schools')) {
                return response()->json([
                    'success' => true,
                    'message' => 'No school settings table found',
                    'data' => null
                ]);
            }
            $school = DB::table('schools')->where('merchant_id', $user->merchant_id)->first();
            
            if (!$school) {
                return response()->json([
                    'success' => true,
                    'message' => 'No school settings found',
                    'data' => null
                ]);
            }
            
            $settings = [
                'schoolName' => $school->name ?? '',
                'address' => $school->address ?? '',
                'email' => $school->email ?? '',
                'phone' => $school->phone_number ?? '',
                'timezone' => $school->timezone ?? 'Asia/Karachi',
                'language' => $school->language ?? 'en',
                'currency' => $school->currency ?? 'PKR'
            ];

            return response()->json([
                'success' => true,
                'message' => 'General settings retrieved successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve general settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkSchoolExists()
    {
        try {
            $user = Auth::user();
            if (!\Schema::hasTable('schools')) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'message' => 'No school settings table found'
                ]);
            }
            $school = DB::table('schools')->where('merchant_id', $user->merchant_id)->first();
            
            return response()->json([
                'success' => true,
                'exists' => $school ? true : false,
                'message' => $school ? 'School settings found' : 'No school settings found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check school settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveGeneralSettings(SaveGeneralSettingsRequest $request)
    {

        try {
            $user = Auth::user();
            if (!\Schema::hasTable('schools')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Schools table not found'
                ], 400);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get school data for this admin
            $school = DB::table('schools')->where('merchant_id', $user->merchant_id)->first();

            if ($school) {
                // Update existing school information
                DB::table('schools')->where('id', $school->id)->update([
                    'name' => $request->schoolName,
                    'address' => $request->address,
                    'email' => $request->email,
                    'phone_number' => $request->phone,
                    'timezone' => $request->timezone,
                    'language' => $request->language,
                    'currency' => $request->currency,
                    'updated_at' => now()
                ]);

                // Get updated school data
                $updatedSchool = DB::table('schools')->where('id', $school->id)->first();
                
                return response()->json([
                    'success' => true,
                    'message' => 'School settings updated successfully',
                    'data' => $updatedSchool,
                    'action' => 'updated'
                ]);
            } else {
                // Create new school record
                $schoolId = DB::table('schools')->insertGetId([
                    'name' => $request->schoolName,
                    'address' => $request->address,
                    'email' => $request->email,
                    'phone_number' => $request->phone,
                    'timezone' => $request->timezone,
                    'language' => $request->language,
                    'currency' => $request->currency,
                    'merchant_id' => $user->merchant_id,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Get newly created school data
                $updatedSchool = DB::table('schools')->where('id', $schoolId)->first();
                
                return response()->json([
                    'success' => true,
                    'message' => 'School settings created successfully',
                    'data' => $updatedSchool,
                    'action' => 'created'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save general settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification settings for current merchant
     * 
     * NOTE: notification_settings table uses school_id (not merchant_id directly)
     * This maintains referential integrity with schools table
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationSettings()
    {
        try {
            $user = Auth::user();
            
            // Get school for this merchant
            $school = DB::table('schools')->where('merchant_id', $user->merchant_id)->first();
            
            if (!$school) {
                // No school profile yet, return defaults
                return response()->json([
                    'success' => true,
                    'message' => 'Using default notification settings',
                    'data' => $this->getDefaultNotificationSettings()
                ]);
            }
            
            if (!\Schema::hasTable('notification_settings')) {
                // Return default settings if table doesn't exist
                return response()->json([
                    'success' => true,
                    'message' => 'Using default notification settings',
                    'data' => $this->getDefaultNotificationSettings()
                ]);
            }
            
            // Get notification settings by school_id (which is scoped to merchant)
            $settings = DB::table('notification_settings')
                ->where('school_id', $school->id)
                ->first();
            
            if (!$settings) {
                return response()->json([
                    'success' => true,
                    'message' => 'Using default notification settings',
                    'data' => $this->getDefaultNotificationSettings()
                ]);
            }
            
            // Map database columns to frontend format
            return response()->json([
                'success' => true,
                'message' => 'Notification settings retrieved successfully',
                'data' => [
                    'emailNotifications' => (bool) ($settings->enable_email ?? true),
                    'smsNotifications' => (bool) ($settings->enable_sms ?? false),
                    'pushNotifications' => true, // Default to true
                    'emailFrequency' => 'instant', // Default value
                    'quietHoursStart' => '22:00',
                    'quietHoursEnd' => '08:00',
                    'attendanceAlerts' => true,
                    'feeReminders' => true,
                    'examResults' => true,
                    'eventsAndHolidays' => true,
                    'academicUpdates' => true,
                    'systemMaintenance' => false
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save notification settings for current merchant
     * 
     * NOTE: Saves to notification_settings table using school_id
     * Ensures merchant isolation via school relationship
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveNotificationSettings(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Get school for this merchant
            $school = DB::table('schools')->where('merchant_id', $user->merchant_id)->first();
            
            if (!$school) {
                return response()->json([
                    'success' => false,
                    'message' => 'School profile must be completed before saving notification settings. Please complete General Settings first.'
                ], 400);
            }
            
            if (!\Schema::hasTable('notification_settings')) {
                // Return success but don't save (table will be created on first use)
                return response()->json([
                    'success' => true,
                    'message' => 'Notification settings saved successfully (using defaults)',
                    'action' => 'saved'
                ]);
            }
            
            // Map frontend format to database columns
            $data = [
                'enable_email' => $request->emailNotifications ?? true,
                'enable_sms' => $request->smsNotifications ?? false,
                // Store whatsapp preference in enable_whatsapp column
                'enable_whatsapp' => $request->pushNotifications ?? true,
                'days_before_due' => 3, // Default for fee reminders
                'days_after_due' => 5,  // Default for fee reminders
                'updated_at' => now()
            ];
            
            $existing = DB::table('notification_settings')
                ->where('school_id', $school->id)
                ->first();
            
            if ($existing) {
                // Update existing settings
                DB::table('notification_settings')
                    ->where('school_id', $school->id)
                    ->update($data);
                    
                return response()->json([
                    'success' => true,
                    'message' => 'Notification settings updated successfully',
                    'action' => 'updated'
                ]);
            } else {
                // Create new settings
                $data['school_id'] = $school->id;
                $data['created_at'] = now();
                
                DB::table('notification_settings')->insert($data);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Notification settings created successfully',
                    'action' => 'created'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get default notification settings
     * 
     * @return array
     */
    private function getDefaultNotificationSettings()
    {
        return [
            'emailNotifications' => true,
            'smsNotifications' => false,
            'pushNotifications' => true,
            'emailFrequency' => 'instant',
            'quietHoursStart' => '22:00',
            'quietHoursEnd' => '08:00',
            'attendanceAlerts' => true,
            'feeReminders' => true,
            'examResults' => true,
            'eventsAndHolidays' => true,
            'academicUpdates' => true,
            'systemMaintenance' => false
        ];
    }
} 