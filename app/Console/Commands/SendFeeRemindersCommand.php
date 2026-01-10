<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Challan;
use App\Models\NotificationEvent;
use App\Models\NotificationSetting;
use App\Jobs\SendReminderJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendFeeRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:send-reminders {--school_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send fee payment reminders via WhatsApp/SMS/Email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schoolId = $this->option('school_id');
        $today = Carbon::today();

        $this->info("Sending fee reminders for date: {$today->toDateString()}");

        // Get unpaid challans
        $challansQuery = Challan::where('status', 'unpaid')
            ->whereNotNull('due_date');

        if ($schoolId) {
            $challansQuery->where('school_id', $schoolId);
        }

        $challans = $challansQuery->get();
        $this->info("Found {$challans->count()} unpaid challans");

        $sent = 0;
        $skipped = 0;

        foreach ($challans as $challan) {
            $school = $challan->school;
            $settings = NotificationSetting::where('school_id', $school->id)->first();

            if (!$settings) {
                $this->warn("No notification settings for school {$school->id}, skipping...");
                $skipped++;
                continue;
            }

            $daysUntilDue = $today->diffInDays($challan->due_date, false);

            // Determine trigger type
            $trigger = null;
            if ($daysUntilDue > 0 && $daysUntilDue <= $settings->days_before_due) {
                $trigger = 'before_due';
            } elseif ($daysUntilDue == 0) {
                $trigger = 'on_due';
            } elseif ($daysUntilDue < 0 && abs($daysUntilDue) <= $settings->days_after_due) {
                $trigger = 'after_due';
            }

            if (!$trigger) {
                continue;
            }

            // Check if notification event already exists
            $existingEvent = NotificationEvent::where('reference_type', 'challan')
                ->where('reference_id', $challan->id)
                ->where('trigger', $trigger)
                ->first();

            if ($existingEvent && $existingEvent->status === 'sent') {
                continue; // Already sent
            }

            // Create or update notification event
            $event = NotificationEvent::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'reference_type' => 'challan',
                    'reference_id' => $challan->id,
                    'trigger' => $trigger,
                ],
                [
                    'type' => 'fee_reminder',
                    'scheduled_at' => now(),
                    'status' => 'pending',
                ]
            );

            // Dispatch jobs for each enabled channel
            $student = $challan->feeInvoice->student;
            $channels = [];

            if ($settings->enable_whatsapp && $student->phone) {
                $channels[] = 'whatsapp';
            }

            if ($settings->enable_sms && $student->phone) {
                $channels[] = 'sms';
            }

            if ($settings->enable_email && $student->email) {
                $channels[] = 'email';
            }

            foreach ($channels as $channel) {
                \App\Jobs\SendReminderJob::dispatch($event->id, $channel, $challan);
            }

            $sent++;
        }

        $this->info("✅ Created {$sent} reminder events, skipped {$skipped}");
        return 0;
    }
}
