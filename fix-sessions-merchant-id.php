<?php

/**
 * ONE-TIME FIX SCRIPT: Update Existing Sessions with merchant_id
 * 
 * PURPOSE:
 * Sessions created BEFORE BelongsToMerchant trait was added might not have merchant_id set.
 * This script backfills merchant_id for existing sessions based on their school's merchant_id.
 * 
 * USAGE:
 * php fix-sessions-merchant-id.php
 * 
 * SAFE TO RUN MULTIPLE TIMES (won't overwrite existing merchant_id values)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "============================================\n";
echo "  SESSION merchant_id BACKFILL SCRIPT      \n";
echo "============================================\n\n";

try {
    // Get all sessions
    $sessions = DB::table('sessions')->get();
    echo "Found {$sessions->count()} session(s) in database\n\n";
    
    $fixed = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($sessions as $session) {
        echo "Processing session #{$session->id}: {$session->name}\n";
        
        // Skip if merchant_id already set
        if ($session->merchant_id) {
            echo "  ✓ merchant_id already set: {$session->merchant_id}\n";
            $skipped++;
            continue;
        }
        
        // Try to get merchant_id from school
        if ($session->school_id) {
            $school = DB::table('schools')->find($session->school_id);
            
            if ($school && $school->merchant_id) {
                // Update session with school's merchant_id
                DB::table('sessions')
                    ->where('id', $session->id)
                    ->update(['merchant_id' => $school->merchant_id]);
                    
                echo "  ✅ Fixed! Set merchant_id to: {$school->merchant_id} (from school)\n";
                $fixed++;
            } else {
                echo "  ⚠️  School not found or has no merchant_id\n";
                $errors++;
            }
        } else {
            echo "  ⚠️  No school_id - cannot infer merchant_id\n";
            $errors++;
        }
    }
    
    echo "\n============================================\n";
    echo "SUMMARY:\n";
    echo "  ✅ Fixed: {$fixed} session(s)\n";
    echo "  ⏭️  Skipped: {$skipped} session(s) (already have merchant_id)\n";
    echo "  ⚠️  Errors: {$errors} session(s) (cannot determine merchant_id)\n";
    echo "============================================\n\n";
    
    if ($fixed > 0) {
        echo "✅ Success! Sessions have been updated.\n";
        echo "👉 Refresh your browser to see the sessions list.\n\n";
    } elseif ($errors > 0) {
        echo "⚠️  Some sessions could not be fixed.\n";
        echo "👉 Check the school_id for those sessions manually.\n\n";
    } else {
        echo "ℹ️  All sessions already have merchant_id set.\n\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Done!\n";
