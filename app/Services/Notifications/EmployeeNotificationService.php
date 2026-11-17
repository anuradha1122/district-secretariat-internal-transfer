<?php

namespace App\Services\Notifications;

use App\Models\TransferApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmployeeNotificationService
{
    /**
     * The calling controller saves this record inside the same transaction
     * as the application update.
     */
    public function record(
        TransferApplication $application,
        string $event
    ): void {
        $messages = [
            'submitted' => [
                'title' => 'Application submitted',
                'message' => 'Your transfer application was submitted for office review.',
            ],

            'office_forwarded' => [
                'title' => 'Application forwarded',
                'message' => 'The office head recorded a recommendation and forwarded your application for district review.',
            ],

            'office_returned' => [
                'title' => 'Application returned for correction',
                'message' => 'The office head returned your application. Open your application to check the required corrections.',
            ],

            'district_approved' => [
                'title' => 'Application approved',
                'message' => 'Your transfer application was approved at district review.',
            ],

            'district_rejected' => [
                'title' => 'Application rejected',
                'message' => 'Your transfer application was rejected at district review. Open your application list to see the reason.',
            ],
        ];

        if (! isset($messages[$event])) {
            throw new InvalidArgumentException(
                "Unknown employee notification event: {$event}"
            );
        }

        $recipient = User::query()->findOrFail($application->user_id);

        $now = now();

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),

            'type' => 'employee_application_update',

            'notifiable_type' => $recipient->getMorphClass(),
            'notifiable_id' => $recipient->getKey(),

            'data' => json_encode([
                'event' => $event,
                'application_id' => $application->id,
                'reference_no' => $application->reference_no,
                'title' => $messages[$event]['title'],
                'message' => $messages[$event]['message'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),

            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}