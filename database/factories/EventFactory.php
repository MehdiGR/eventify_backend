<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+1 month');
        $endDate = clone $startDate;
        $endDate->modify('+'.rand(1, 5).' days');

        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'organizer_id' => User::factory(),
            'location' => $this->faker->city,
            'max_participants' => rand(10, 1000),
            'image' => 'https://picsum.photos/200/300',
            'status' => $this->faker->randomElement([
                Event::STATUS_DRAFT,
                Event::STATUS_PUBLISHED,
                Event::STATUS_CANCELED,
                Event::STATUS_COMPLETED,
            ]),
            'visibility' => $this->faker->randomElement([
                Event::VISIBILITY_PUBLIC,
                Event::VISIBILITY_PRIVATE,
                Event::VISIBILITY_GROUP,
            ]),
        ];
    }

    // Optional: Define custom states for different scenarios
    public function published()
    {
        return $this->state(fn () => [
            'status' => Event::STATUS_PUBLISHED,
        ]);
    }

    public function withParticipants($count = 3)
    {
        return $this->afterCreating(function (Event $event) use ($count) {
            $event->participants()->attach(
                User::factory()->count($count)->create()
            );
        });
    }
}
