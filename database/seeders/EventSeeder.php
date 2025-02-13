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

        $events = [
            [
                'name' => 'Event 1',
                'description' => 'Description for Event 1',
                'start_date' => now(),
                'end_date' => now()->addDays(7),
            ],
            [
                'name' => 'Event 2',
                'description' => 'Description for Event 2',
                'start_date' => now()->addDays(8),
                'end_date' => now()->addDays(15),
            ],
        ];

        foreach ($events as $eventData) {
            $event = Event::create(array_merge($eventData, ['organizer_id' => $organizer->id]));
            $event->participants()->attach($participant->id);
        }
    }
}
