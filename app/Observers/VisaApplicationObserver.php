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
           // Check if 'status' field was changed
           if ($visaApplication->isDirty('status')) {
            Log::info('Visa status changed', [
                'visa_id' => $visaApplication->id,
                'old_status' => $visaApplication->getOriginal('status'),
                'new_status' => $visaApplication->status,
                'user_email' => optional($visaApplication->user)->email,
            ]);
            // Send email if user has an email
            if ($visaApplication->user && $visaApplication->user->email) {
                Mail::to($visaApplication->user->email)->send(new VisaStatusUpdated($visaApplication));
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
