<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\ProductionSection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WorkflowNotificationService
{
    /**
     * Notify the supervisor of the target section about a job transfer.
     */
    public static function notifyTransfer($job, $targetSectionSlug, $specificLineName = null)
    {
        // Find the target section
        $query = ProductionSection::where('slug', $targetSectionSlug);
        
        // If specific line (e.g. Line 01), search by name instead of slug if slug not sufficient
        if ($specificLineName) {
            $query = ProductionSection::where('name', $specificLineName);
        }

        $section = $query->first();

        if (!$section || !$section->supervisor || !$section->supervisor->email) {
            Log::warning("Workflow Notification: No supervisor or email found for section {$targetSectionSlug} / {$specificLineName}");
            return;
        }

        $supervisor = $section->supervisor;

        try {
            Mail::send('erp.emails.job_transfer', [
                'supervisorName' => $supervisor->name,
                'sectionName' => $section->name,
                'job' => $job
            ], function ($message) use ($supervisor) {
                $message->to($supervisor->email)
                        ->subject('New Job Incoming - Action Required');
            });
            
            Log::info("Transfer email sent to {$supervisor->email} for Job {$job->job_number}");

        } catch (\Exception $e) {
            Log::error("Failed to send transfer email: " . $e->getMessage());
        }
    }
}
