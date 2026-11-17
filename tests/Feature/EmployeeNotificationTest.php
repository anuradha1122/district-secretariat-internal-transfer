<?php

namespace Tests\Feature;

use App\Models\TransferApplication;
use App\Models\User;
use App\Services\Notifications\EmployeeNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class EmployeeNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function insertNotification(
        User $user,
        string $title = 'Application submitted'
    ): string {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'employee_application_update',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'data' => json_encode([
                'title' => $title,
                'message' => 'Your application has been updated.',
                'reference_no' => 'TEST-001',
            ], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_guests_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_can_see_only_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->insertNotification($user, 'My update');
        $this->insertNotification($otherUser, 'Other user update');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/index')
                ->where('unreadCount', 1)
                ->has('notifications.data', 1)
                ->where('notifications.data.0.title', 'My update')
            );
    }

    public function test_a_user_can_mark_their_notification_as_read(): void
    {
        $user = User::factory()->create();

        $id = $this->insertNotification($user);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull(
            DB::table('notifications')->where('id', $id)->value('read_at')
        );

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('unreadCount', 0)
            );
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $id = $this->insertNotification($otherUser);

        $this->actingAs($user)
            ->post(route('notifications.read', $id))
            ->assertNotFound();

        $this->assertNull(
            DB::table('notifications')->where('id', $id)->value('read_at')
        );
    }

    public function test_mark_all_as_read_affects_only_the_current_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstId = $this->insertNotification($user);
        $secondId = $this->insertNotification($user);
        $otherId = $this->insertNotification($otherUser);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull(
            DB::table('notifications')->where('id', $firstId)->value('read_at')
        );

        $this->assertNotNull(
            DB::table('notifications')->where('id', $secondId)->value('read_at')
        );

        $this->assertNull(
            DB::table('notifications')->where('id', $otherId)->value('read_at')
        );
    }

    public function test_a_notification_is_rolled_back_with_its_transaction(): void
    {
        $user = User::factory()->create();

        // The service needs the application identity and owner; no application
        // record is created or changed in this transaction test.
        $application = new TransferApplication;
        $application->id = 123;
        $application->user_id = $user->id;
        $application->reference_no = 'TEST-123';

        try {
            DB::transaction(function () use ($application): void {
                app(EmployeeNotificationService::class)
                    ->record($application, 'submitted');

                throw new RuntimeException('Roll back this transaction.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Roll back this transaction.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('notifications', 0);
    }
}