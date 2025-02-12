<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizer = User::where('email', 'organizer@example.com')->first();
        $participant = User::where('email', 'participant@example.com')->first();

        $event = Event::create(
            [
                'name' => 'Sample Event',
                'description' => 'This is a sample event for testing.',
                'start_date' => now(),
                'end_date' => now()->addDays(7),
                'organizer_id' => $organizer->id,
            ]
        );

        // Attach a participant to the event
        $event->participants()->attach($participant->id);
    }
}
