<?php

namespace Tests\Unit;

use App\Http\Controllers\EventController;
use App\Jobs\SendEventNotification;
use App\Models\User;
use Event;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Queue;

class EventControllerTest extends TestCase
{
    // use RefreshDatabase; // Reset the database after each test

    /**
     * Test that a user can register for an event and receive an email notification.
     */
    public function test_register_for_event()
    {
        // Step 1: Create a user and event
        $user = User::factory()->create();
        $event = Event::factory()->create();

        // Step 2: Mock the queue
        Queue::fake();

        // Step 3: Simulate registration
        $controller = new EventController;
        $request = new Request(
            [
                'event_id' => $event->id,
            ]
        );
        $request->setUserResolver(
            function () use ($user) {
                return $user; // Simulate an authenticated user
            }
        );

        $response = $controller->registerForEvent($request);

        // Step 4: Assert the response
        $this->assertTrue($response->getData()->success);
        $this->assertDatabaseHas(
            'event_user', [
                'user_id' => $user->id,
                'event_id' => $event->id,
            ]
        );

        // Step 5: Assert the job was dispatched
        Queue::assertPushed(
            SendEventNotification::class,
            function ($job) use ($event, $user) {
                return $job->event->id === $event->id && $job->user->id === $user->id;
            }
        );
    }
}
