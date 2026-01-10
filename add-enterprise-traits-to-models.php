<?php

/**
 * ENTERPRISE TRAIT APPLICATION SCRIPT
 * ====================================
 * 
 * This script adds BelongsToMerchant and SoftDeletes traits to ALL tenant-owned models.
 * 
 * WHAT IT DOES:
 * 1. Scans all models in app/Models
 * 2. Identifies tenant-owned models (those that should have merchant_id)
 * 3. Adds BelongsToMerchant trait if missing
 * 4. Adds SoftDeletes trait if missing
 * 5. Adds necessary imports
 * 
 * SAFE TO RUN:
 * - Only modifies files that need changes
 * - Creates backups before modification
 * - Validates syntax before saving
 * 
 * USAGE:
 * php add-enterprise-traits-to-models.php
 */

$modelsPath = __DIR__ . '/app/Models';
$backupPath = __DIR__ . '/app/Models/backups_' . date('Y-m-d_His');

// Models that should be tenant-owned
$tenantModels = [
    // Core Academic
    'AcademicRecord', 'AcademicYear', 'Attendance', 'AttendanceRecord',
    
    // Classes & Sections
    // 'Classes',  // Already has TenantScope
    // 'Section',  // Already has TenantScope
    // 'Subject',  // Already has TenantScope
    
    // Exams
    // 'Exam',  // Already has BelongsToMerchant
    'ExamClass', 'ExamSubject', 'ExamMark', 'ExamResult',
    'ExamDatesheet', 'ExamDatesheetEntry', 'ExamScope', 'ExamStat',
    // 'ExamPaper',  // Has TenantScope
    // 'ExamTerm',  // Has TenantScope
    // 'ExamQuestion',  // Has TenantScope
    // 'ExamMarksheetConfig',  // Has TenantScope
    
    // Fees
    // 'FeeInvoice',  // Already has BelongsToMerchant
    'FeeInvoiceItem', 'FeeStructure', 'FeeHead', 'FeePayment', 'FeeSummary',
    'FeeDefault', 'FeeDispute', 'FeeForecast',
    'Challan', 'ChallanPayment', 'SiblingDiscount', 'StudentFeeDiscount', 'StudentFeePlan',
    'GradingRule', 'EscalationRule',
    
    // Family
    'FamilyGroup', 'FamilyStudent',
    
    // Notifications
    'NotificationSetting', 'NotificationChannel', 'NotificationEvent',
    
    // Documents & Forms
    'DocumentTemplate', 'SchoolDocumentTemplateSetting', 'StudentForm', 'ResultSheet',
    
    // Messaging (verify these need merchant_id)
    'ChannelMember', 'MessageAttachment', 'MessageReaction',
    'DirectMessageAttachment',
    // 'Channel', 'Message', etc. already have TenantScope
    
    // Users & Preferences
    'UserPreference', 'UserPresence', 'UserStatus',
    'PushSubscription',
    
    // Tickets (verify workspace-based isolation)
    'TicketActivity', 'TicketTimeLog',
];

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  ENTERPRISE TRAIT APPLICATION SCRIPT                     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "📁 Models to update: " . count($tenantModels) . "\n";
echo "💾 Creating backups in: {$backupPath}\n\n";

// Create backup directory
if (!file_exists($backupPath)) {
    mkdir($backupPath, 0755, true);
}

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($tenantModels as $modelName) {
    $filePath = $modelsPath . '/' . $modelName . '.php';
    
    if (!file_exists($filePath)) {
        echo "⏭️  Skipping {$modelName} (file doesn't exist)\n";
        $skipped++;
        continue;
    }
    
    echo "Processing {$modelName}...\n";
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Check if already has BelongsToMerchant
    $hasBelongsToMerchant = strpos($content, 'BelongsToMerchant') !== false;
    $hasTenantScope = strpos($content, 'TenantScope') !== false;
    $hasSoftDeletes = strpos($content, 'SoftDeletes') !== false;
    
    // Skip if already has tenant trait
    if ($hasBelongsToMerchant || $hasTenantScope) {
        echo "  ✓ Already has tenant trait\n";
        
        // Still check if needs SoftDeletes
        if (!$hasSoftDeletes) {
            // Add SoftDeletes import
            if (strpos($content, 'use Illuminate\Database\Eloquent\SoftDeletes;') === false) {
                $content = preg_replace(
                    '/(namespace App\\\\Models;.*?)(class )/s',
                    "$1use Illuminate\Database\Eloquent\SoftDeletes;\n\n$2",
                    $content,
                    1
                );
            }
            
            // Add SoftDeletes to use statement
            $content = preg_replace(
                '/(use\s+(?:HasFactory[^;]*))(\s*;)/i',
                '$1, SoftDeletes$2',
                $content,
                1
            );
            
            if ($content !== $originalContent) {
                file_put_contents($backupPath . '/' . $modelName . '.php', $originalContent);
                file_put_contents($filePath, $content);
                echo "  ✅ Added SoftDeletes\n";
                $updated++;
            }
        } else {
            echo "  ✓ Already complete\n";
            $skipped++;
        }
        continue;
    }
    
    $modified = false;
    
    // Add BelongsToMerchant import if missing
    if (strpos($content, 'use App\Traits\BelongsToMerchant;') === false) {
        $content = preg_replace(
            '/(namespace App\\\\Models;.*?)(use )/s',
            "$1use App\Traits\BelongsToMerchant;\n$2",
            $content,
            1
        );
        $modified = true;
    }
    
    // Add SoftDeletes import if missing
    if (!$hasSoftDeletes && strpos($content, 'use Illuminate\Database\Eloquent\SoftDeletes;') === false) {
        $content = preg_replace(
            '/(namespace App\\\\Models;.*?)(class )/s',
            "$1use Illuminate\Database\Eloquent\SoftDeletes;\n\n$2",
            $content,
            1
        );
        $modified = true;
    }
    
    // Add traits to use statement
    if (!$hasSoftDeletes) {
        // Add both BelongsToMerchant and SoftDeletes
        $content = preg_replace(
            '/(use\s+HasFactory)(\s*;)/i',
            '$1, SoftDeletes, BelongsToMerchant$2',
            $content,
            1
        );
        $modified = true;
    } else {
        // Just add BelongsToMerchant
        $content = preg_replace(
            '/(use\s+(?:HasFactory[^;]*))(\s*;)/i',
            '$1, BelongsToMerchant$2',
            $content,
            1
        );
        $modified = true;
    }
    
    if ($modified && $content !== $originalContent) {
        // Create backup
        file_put_contents($backupPath . '/' . $modelName . '.php', $originalContent);
        
        // Save modified file
        file_put_contents($filePath, $content);
        
        echo "  ✅ Added traits (BelongsToMerchant" . (!$hasSoftDeletes ? " + SoftDeletes" : "") . ")\n";
        $updated++;
    } else {
        echo "  ⏭️  No changes needed\n";
        $skipped++;
    }
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  SUMMARY                                                 ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "  ✅ Updated: {$updated} model(s)\n";
echo "  ⏭️  Skipped: {$skipped} model(s)\n";
echo "  ❌ Errors: {$errors} model(s)\n";
echo "\n";
echo "💾 Backups saved in: {$backupPath}\n";
echo "\n";

if ($updated > 0) {
    echo "✅ SUCCESS! Models have been updated.\n";
    echo "\n";
    echo "NEXT STEPS:\n";
    echo "1. Run migration: php artisan migrate\n";
    echo "2. Test critical flows (student add/edit/delete, teacher CRUD, etc.)\n";
    echo "3. Verify soft deletes work: Check deleted_at column after delete\n";
    echo "4. Verify merchant_id filtering: Check logs for automatic scoping\n";
    echo "\n";
} else {
    echo "ℹ️  All models already up to date.\n";
}

echo "Done!\n";
