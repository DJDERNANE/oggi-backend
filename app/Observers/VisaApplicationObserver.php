<?php

namespace App\Observers;

use App\Models\VisaApplication;
use App\Mail\VisaStatusUpdated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


class VisaApplicationObserver
{
    /**
     * Handle the VisaApplication "created" event.
     */
    public function created(VisaApplication $visaApplication): void
    {
        //
    }

    /**
     * Handle the VisaApplication "updated" event.
     */
    public function updated(VisaApplication $visaApplication): void
    {
        $oldStatus = $visaApplication->getOriginal('status');
        $newStatus = $visaApplication->status;
        $userEmail = optional($visaApplication->user)->email;
        // Check if 'status' field was changed
        if ($visaApplication->isDirty('status')) {
            Log::info('Visa status changed', [
                'visa_id' => $visaApplication->id,
                'old_status' => $visaApplication->getOriginal('status'),
                'new_status' => $visaApplication->status,
                'user_email' => optional($visaApplication->user)->email,
            ]);
            // Send email if user has an email
            // Send email if user has an email
            if ($userEmail) {
                Log::info("Preparing to send visa status update email to: {$userEmail}");

                try {
                    Mail::to($userEmail)->send(new VisaStatusUpdated($visaApplication));

                    Log::info("Visa status update email successfully sent to: {$userEmail}", [
                        'visa_id' => $visaApplication->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to send visa status update email to: {$userEmail}", [
                        'visa_id' => $visaApplication->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning("User has no email. Cannot send visa status update notification.", [
                    'visa_id' => $visaApplication->id,
                ]);
            }
        }
    }

    /**
     * Handle the VisaApplication "deleted" event.
     */
    public function deleted(VisaApplication $visaApplication): void
    {
        //
    }

    /**
     * Handle the VisaApplication "restored" event.
     */
    public function restored(VisaApplication $visaApplication): void
    {
        //
    }

    /**
     * Handle the VisaApplication "force deleted" event.
     */
    public function forceDeleted(VisaApplication $visaApplication): void
    {
        //
    }
}
