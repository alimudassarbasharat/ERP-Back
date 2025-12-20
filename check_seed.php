<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

$tables = [
    'departments', 'academic_years', 'classes', 'sections', 'subjects', 'teachers', 'students', 'fee_summaries',
];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo $table . ': ' . $count . "\n";
    } else {
        echo $table . ": (table not found)\n";
    }
}

if (Schema::hasTable('students')) {
    $cols = [];
    // Determine available name columns
    if (Schema::hasColumn('students', 'first_name')) $cols[] = 'first_name';
    if (Schema::hasColumn('students', 'last_name')) $cols[] = 'last_name';
    if (Schema::hasColumn('students', 'name')) $cols[] = 'name';
    $select = array_merge(['id'], $cols);
    $sample = DB::table('students')->select($select)->limit(3)->get();
    echo "\nSample students:\n";
    foreach ($sample as $row) {
        $nameParts = [];
        foreach ($cols as $c) { $nameParts[] = $row->$c; }
        echo '- #' . $row->id . ' ' . trim(implode(' ', $nameParts)) . "\n";
    }
}

echo "\nSeed check complete.\n";


