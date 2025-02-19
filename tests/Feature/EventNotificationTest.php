<?php

namespace Tests\Feature;

use App\Events\NewEventCreated;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade; // Renamed facade
use Tests\TestCase; // Use the correct TestCase

class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_creation_triggers_broadcast()
    {
        // Use the Event facade for faking, not your model
        EventFacade::fake();

        $user = User::factory()->create();
        $event = Event::factory()->create(['title' => 'Test Event']);

        // Add assertions to verify event broadcasting
        EventFacade::assertDispatched(NewEventCreated::class, function ($e) use ($event) {
            return $e->event->id === $event->id;
        });
    }

    public function test_event_broadcasts_correct_data()
    {
        $event = Event::factory()->create(['title' => 'Test Event']);
        $broadcastedEvent = new NewEventCreated($event);

        // Channel assertion
        $this->assertEquals('events', $broadcastedEvent->broadcastOn()[0]->name);

        // Event name assertion
        $this->assertEquals('new-event-created', $broadcastedEvent->broadcastAs());

        // Payload assertion
        $this->assertEquals(
            'A new event has been created: '.$event->title,
            $broadcastedEvent->broadcastWith()['message']
        );
    }

    public function test_pusher_configuration()
    {
        // Test environment variables
        $this->assertEquals('pusher', env('BROADCAST_DRIVER'));

        // Test config values
        $this->assertEquals('pusher', config('broadcasting.default'));
        $this->assertEquals(env('PUSHER_APP_KEY'), config('broadcasting.connections.pusher.key'));
    }
}
