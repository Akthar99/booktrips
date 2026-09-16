<?php

namespace App\Services;

use App\Enums\ScheduleType;
use App\Models\Package;
use Illuminate\Support\Carbon;

class ScheduleService
{
    /**
     * Whether the package runs on the given date (weekday + date window).
     */
    public function allowsDate(Package $package, string $iso): bool
    {
        $weekdays = $package->weekdays;

        if (is_array($weekdays) && $weekdays !== [] && ! in_array($this->weekdayOf($iso), array_map('intval', $weekdays), true)) {
            return false;
        }

        if ($package->schedule_type === ScheduleType::Range) {
            if ($package->schedule_start && $iso < $package->schedule_start->toDateString()) {
                return false;
            }

            if ($package->schedule_end && $iso > $package->schedule_end->toDateString()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a stay from check-in to check-out lands on allowed days.
     */
    public function allowsStay(Package $package, string $checkIn, string $checkOut): bool
    {
        if (! $this->allowsDate($package, $checkIn)) {
            return false;
        }

        if ($checkOut !== '' && $checkOut !== $checkIn && ! $this->allowsDate($package, $checkOut)) {
            return false;
        }

        return true;
    }

    public function weekdayOf(string $iso): int
    {
        return Carbon::parse($iso)->dayOfWeek;
    }
}
