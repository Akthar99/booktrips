<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Mail\HostBookingNoticeMail;
use App\Models\Booking;
use App\Models\User;
use App\Services\MailService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class EscalateStaleBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booktrips:escalate-stale-bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flag booking requests a host has not answered within the escalation window';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notifications, MailService $mail): int
    {
        $hours = (int) config('booktrips.escalation_hours');
        $cutoff = now()->subHours($hours);

        $stale = Booking::query()
            ->with(['package.business', 'user'])
            ->where('status', BookingStatus::Requested)
            ->where('escalated', false)
            ->where('created_at', '<', $cutoff)
            ->get();

        $admins = User::query()
            ->where('role', UserRole::Admin)
            ->where('active', true)
            ->get();

        foreach ($stale as $booking) {
            $booking->forceFill([
                'escalated' => true,
                'escalated_at' => now(),
            ])->save();

            $package = $booking->package;
            $business = $package?->business;

            $notifications->notifyAdmins(
                'escalate',
                "Unanswered request {$booking->booking_code}",
                ($business->name ?? 'A host')." has not confirmed {$booking->booking_code} after {$hours} hours.",
                $booking->id,
            );

            if ($package && $business?->email) {
                $mail->quietSend($business->email, new HostBookingNoticeMail($booking, $package, true));
            }

            if ($package) {
                foreach ($admins as $admin) {
                    $mail->quietSend($admin->email, new HostBookingNoticeMail($booking, $package, true));
                }
            }
        }

        $this->info("Escalated {$stale->count()} stale booking request(s).");

        return self::SUCCESS;
    }
}
