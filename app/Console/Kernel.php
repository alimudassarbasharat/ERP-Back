<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CreatePassportClient::class,
        Commands\GenerateFeeSummariesCommand::class,
        Commands\ApplyLateFeeCommand::class,
    ];

    /** 
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('fee:generate')->monthlyOn(1, '00:00'); // 1st of every month at midnight->timezone('Asia/Karachi'); // Or your local timezone

        $schedule->command('fee:apply-late')->dailyAt('00:10')->timezone('Asia/Karachi');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
