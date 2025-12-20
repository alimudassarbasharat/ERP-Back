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
            $school = DB::table('schools')->where('admin_id', $user->id)->first();
            
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
            $school = DB::table('schools')->where('admin_id', $user->id)->first();
            
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
            $school = DB::table('schools')->where('admin_id', $user->id)->first();

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
                    'admin_id' => $user->id,
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
} 