<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\NotificationEvent;
use App\Models\NotificationChannel;
use App\Models\Challan;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $eventId,
        public string $channel,
        public Challan $challan
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $event = NotificationEvent::find($this->eventId);
        
        if (!$event) {
            Log::warning("Notification event {$this->eventId} not found");
            return;
        }

        $challan = $this->challan;
        $student = $challan->feeInvoice->student;

        // Get recipient based on channel
        $recipient = match($this->channel) {
            'whatsapp', 'sms' => $student->phone ?? $student->phone_number ?? null,
            'email' => $student->email ?? null,
            default => null,
        };

        if (!$recipient) {
            Log::warning("No recipient found for channel {$this->channel} for student {$student->id}");
            $this->createChannelRecord($event, 'failed', 'No recipient found');
            return;
        }

        try {
            // Send notification based on channel
            $success = match($this->channel) {
                'whatsapp' => $this->sendWhatsApp($recipient, $challan),
                'sms' => $this->sendSMS($recipient, $challan),
                'email' => $this->sendEmail($recipient, $challan),
                default => false,
            };

            if ($success) {
                $this->createChannelRecord($event, 'sent', null);
                
                // Update event status if all channels are sent
                $this->updateEventStatus($event);
            } else {
                $this->createChannelRecord($event, 'failed', 'Sending failed');
            }
        } catch (\Exception $e) {
            Log::error("Error sending {$this->channel} reminder: " . $e->getMessage());
            $this->createChannelRecord($event, 'failed', $e->getMessage());
        }
    }

    protected function sendWhatsApp(string $recipient, Challan $challan): bool
    {
        // TODO: Implement WhatsApp API integration (Twilio, WhatsApp Business API, etc.)
        // For now, just log
        Log::info("WhatsApp reminder sent to {$recipient} for challan {$challan->challan_no}");
        return true;
    }

    protected function sendSMS(string $recipient, Challan $challan): bool
    {
        // TODO: Implement SMS API integration (Twilio, etc.)
        // For now, just log
        Log::info("SMS reminder sent to {$recipient} for challan {$challan->challan_no}");
        return true;
    }

    protected function sendEmail(string $recipient, Challan $challan): bool
    {
        // TODO: Implement email sending using Laravel Mail
        // For now, just log
        Log::info("Email reminder sent to {$recipient} for challan {$challan->challan_no}");
        return true;
    }

    protected function createChannelRecord(NotificationEvent $event, string $status, ?string $error): void
    {
        $student = $this->challan->feeInvoice->student;
        $recipient = match($this->channel) {
            'whatsapp', 'sms' => $student->phone ?? $student->phone_number ?? null,
            'email' => $student->email ?? null,
            default => null,
        };

        NotificationChannel::create([
            'event_id' => $event->id,
            'channel' => $this->channel,
            'recipient' => $recipient ?? '',
            'status' => $status,
            'provider_response' => $error,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    protected function updateEventStatus(NotificationEvent $event): void
    {
        $channels = $event->channels;
        $allSent = $channels->every(fn($channel) => $channel->status === 'sent');
        $anyFailed = $channels->contains(fn($channel) => $channel->status === 'failed');

        if ($allSent) {
            $event->status = 'sent';
            $event->save();
        } elseif ($anyFailed && $channels->where('status', 'sent')->count() > 0) {
            // Partial success - keep as pending or mark as sent with some failures
            $event->status = 'sent';
            $event->save();
        }
    }
}
