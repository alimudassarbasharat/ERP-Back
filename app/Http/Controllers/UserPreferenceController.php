<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    /**
     * Get user preferences for report templates.
     */
    public function getReportTemplatePreferences()
    {
        try {
            $userId = Auth::id();
            
            $preferences = UserPreference::getUserPreferences($userId, 'report_template');
            
            return response()->json([
                'success' => true,
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save user preference for report template.
     */
    public function saveReportTemplatePreference(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:character,challan,idCard,attendance,progress,leaving',
                'template' => 'required|string'
            ]);

            $userId = Auth::id();
            
            UserPreference::setUserPreference(
                $userId,
                'report_template',
                $request->type,
                $request->template
            );

            return response()->json([
                'success' => true,
                'message' => 'Preference saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save preference',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available templates for different report types.
     */
    public function getAvailableTemplates()
    {
        try {
            $templates = [
                'character' => [
                    ['id' => 'modern-gradient', 'name' => 'Modern Gradient', 'description' => 'Elegant gradient background with modern typography', 'premium' => true],
                    ['id' => 'classic-formal', 'name' => 'Classic Formal', 'description' => 'Traditional formal design with school seal', 'premium' => false],
                    ['id' => 'royal-premium', 'name' => 'Royal Premium', 'description' => 'Luxurious golden border with royal styling', 'premium' => true],
                    ['id' => 'minimal-clean', 'name' => 'Minimal Clean', 'description' => 'Clean minimalist design with sharp lines', 'premium' => false],
                ],
                'challan' => [
                    ['id' => 'default', 'name' => 'Default', 'description' => 'Standard fee challan format', 'premium' => false],
                    ['id' => 'modern', 'name' => 'Modern', 'description' => 'Contemporary design with clean layout', 'premium' => false],
                    ['id' => 'classic', 'name' => 'Classic', 'description' => 'Traditional format with formal styling', 'premium' => false],
                    ['id' => 'corporate', 'name' => 'Corporate', 'description' => 'Professional business style', 'premium' => true],
                    ['id' => 'elegant', 'name' => 'Elegant', 'description' => 'Sophisticated design with premium feel', 'premium' => true],
                    ['id' => 'minimal', 'name' => 'Minimal', 'description' => 'Simple and clean design', 'premium' => false],
                    ['id' => 'traditional', 'name' => 'Traditional', 'description' => 'Classic Pakistani style', 'premium' => false],
                    ['id' => 'professional', 'name' => 'Professional', 'description' => 'Formal business layout', 'premium' => true],
                    ['id' => 'school', 'name' => 'School', 'description' => 'Designed for primary/secondary schools', 'premium' => false],
                    ['id' => 'university', 'name' => 'University', 'description' => 'Higher education institution format', 'premium' => true],
                ],
                'idCard' => [
                    ['id' => 'modern-3d', 'name' => 'Modern 3D', 'description' => '3D layered design with holographic effects', 'premium' => true],
                    ['id' => 'corporate-sleek', 'name' => 'Corporate Sleek', 'description' => 'Professional corporate card design', 'premium' => false],
                    ['id' => 'student-vibrant', 'name' => 'Student Vibrant', 'description' => 'Colorful and energetic student design', 'premium' => true],
                    ['id' => 'classic-photo', 'name' => 'Classic Photo', 'description' => 'Traditional ID card with photo frame', 'premium' => false],
                ],
                'attendance' => [
                    ['id' => 'chart-analytics', 'name' => 'Chart Analytics', 'description' => 'Advanced charts and analytics view', 'premium' => true],
                    ['id' => 'calendar-grid', 'name' => 'Calendar Grid', 'description' => 'Calendar-based attendance visualization', 'premium' => false],
                    ['id' => 'dashboard-style', 'name' => 'Dashboard Style', 'description' => 'Modern dashboard with progress bars', 'premium' => true],
                    ['id' => 'simple-table', 'name' => 'Simple Table', 'description' => 'Clean table format with totals', 'premium' => false],
                ],
                'progress' => [
                    ['id' => 'default', 'name' => 'Default Report Card', 'description' => 'Standard report card template with grades and attendance', 'premium' => false],
                    ['id' => 'modern', 'name' => 'Modern Report Card', 'description' => 'Contemporary design with detailed performance analysis', 'premium' => true],
                    ['id' => 'detailed', 'name' => 'Detailed Report Card', 'description' => 'Comprehensive report with subject-wise breakdown', 'premium' => true],
                ],
                'leaving' => [
                    ['id' => 'official-formal', 'name' => 'Official Formal', 'description' => 'Formal government-style certificate', 'premium' => true],
                    ['id' => 'school-branded', 'name' => 'School Branded', 'description' => 'School logo and branding prominent', 'premium' => false],
                    ['id' => 'certificate-premium', 'name' => 'Certificate Premium', 'description' => 'Premium certificate with special border', 'premium' => true],
                    ['id' => 'standard-format', 'name' => 'Standard Format', 'description' => 'Standard leaving certificate format', 'premium' => false],
                ]
            ];

            return response()->json([
                'success' => true,
                'templates' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 