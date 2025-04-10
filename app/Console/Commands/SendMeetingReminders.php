<?php

namespace App\Console\Commands;

use App\Models\MeetingRequest;
use App\Models\User;
use App\Notifications\MeetingRequestReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming meetings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get meetings scheduled between 24 and 25 hours from now
        $startTime = Carbon::now()->addHours(24);
        $endTime = Carbon::now()->addHours(25);

        $meetings = MeetingRequest::where('status', 'scheduled')
            ->whereBetween('preferred_time', [$startTime, $endTime])
            ->get();

        $this->info('Found ' . $meetings->count() . ' upcoming meetings to send reminders for.');

        foreach ($meetings as $meeting) {
            $user = User::where('email', $meeting->user_email)->first();

            if ($user) {
                $user->notify(new MeetingRequestReminder($meeting));
                $this->info('Sent reminder for meeting #' . $meeting->id . ' to ' . $meeting->user_email);
            } else {
                $this->warn('User not found for email: ' . $meeting->user_email);
            }
        }

        $this->info('Meeting reminder process completed.');

        return 0;
    }
}
