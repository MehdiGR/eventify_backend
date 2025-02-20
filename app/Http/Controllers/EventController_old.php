<?php

namespace App\Http\Controllers;

use App\Enums\ROLE;
use App\Events\NewEventCreated;
use App\Jobs\SendEventNotification;
use App\Models\Event;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends CrudController
{
    protected $table = 'events';

    protected $modelClass = Event::class;

    public function __construct()
    {
        // i was trying to modify the permissions for readAll and readOne
        // Allow readAll and readOne to be publicly accessible
        // $this->restricted = array_diff($this->restricted, ['read_all', 'read_one']);
    }

    protected function getTable()
    {
        return $this->table;
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }

    // protected function getReadAllQuery(): Builder
    // {
    //     return $this->model()->orderBy('name', 'asc');
    // }
    public function scopeOrganizedBy($query, $userId)
    {
        return $query->where('organizer_id', $userId);
    }
    // protected function getReadAllQuery(): Builder
    // {
    //     $query = parent::getReadAllQuery(); // Start with the base query

    //     // Check if organizer_id is provided in the request
    //     if (request()->has('organizer_id')) {
    //         $organizerId = request()->input('organizer_id');
    //         $query = $query->organizedBy($organizerId); // Apply the scope
    //     }

    //     return $query;
    // }
    public function readAll(Request $request)
    {
        try {
            // Handle public organizer filtering
            if (! $request->user() && $request->has('organizer_id')) {
                $events = Event::where('organizer_id', $request->organizer_id)
                    ->orderBy('start_date', 'desc')
                    ->paginate($request->input('per_page', 50));

                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => $events->items(),
                        'meta' => [
                            'current_page' => $events->currentPage(),
                            'last_page' => $events->lastPage(),
                            'total_items' => $events->total(),
                        ],
                    ],
                ]);
            }

            // Add permission bypass for organizer_id filtering
            if ($request->has('organizer_id') && $request->user()) {
                $user = $request->user();
                if ($user->hasRole(ROLE::ADMIN) || $user->id == $request->organizer_id) {
                    $this->restricted = array_diff($this->restricted, ['read_all']);
                }
            }

            return parent::readAll($request);
        } catch (\Exception $e) {
            Log::error('EventController readAll error: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }
    // public function readAll(Request $request)
    // {
    //     try {
    //         // Retrieve query parameters for pagination and filtering
    //         $perPage = $request->input('per_page', 50); // Default to 50 items per page
    //         $query = Event::query();

    //         // Apply filters if provided
    //         $filters = $request->input('filter'); // Safely retrieve the filter parameter
    //         if ($filters && is_array($filters)) {
    //             foreach ($filters as $column => $value) {
    //                 $query->where($column, 'like', '%' . $value . '%');
    //             }
    //         }

    //         // Apply sorting if provided
    //         $orders = $request->input('order'); // Safely retrieve the order parameter
    //         if ($orders && is_array($orders)) {
    //             foreach ($orders as $column => $direction) {
    //                 $query->orderBy($column, $direction);
    //             }
    //         }

    //         // Paginate or retrieve all items based on the request
    //         if ($perPage === 'all') {
    //             $items = $query->get();
    //         } else {
    //             $items = $query->paginate($perPage);
    //         }

    //         // Format the response
    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'items' => $items->items() ?? $items,
    //                 'meta' => [
    //                     'current_page' => $items->currentPage() ?? 1,
    //                     'last_page' => $items->lastPage() ?? 1,
    //                     'total_items' => $items->total() ?? count($items),
    //                 ],
    //             ],
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Error caught in function EventController.readAll: ' . $e->getMessage());
    //         \Log::error($e->getTraceAsString());
    //         return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
    //     }
    // }
    protected function afterCreateOne($event, Request $request)
    {
        try {
            DB::afterCommit(function () use ($event, $request) {
                $user = $request->user();
                if (! $user) {
                    Log::error('User not found in request.');

                    return;
                }
                Log::info('Assigning permissions to user...');
                $user->givePermission("events.{$event->id}.update_own");
                $user->givePermission("events.{$event->id}.delete_own");

                Log::info('Dispatching NewEventCreated event...');
                event(new NewEventCreated($event));

                Log::info('Dispatching SendEventNotification job...');
                SendEventNotification::dispatch($event, $user);
            });
        } catch (\Exception $e) {
            Log::error('Error processing event creation: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'errors' => [__('common.file_upload_error')],
            ]);
        }
    }

    protected function afterUpdateOne($event, Request $request)
    {
        // Notify participants if the event details have changed significantly
        if (isset($request->start_date) || isset($request->location)) {
            SendEventNotification::dispatch($event, $request->user(), 'update');
        }
    }

    protected function registerForEvent(Request $request)
    {
        try {
            $request->validate([
                'event_id' => 'required|exists:events,id',
            ]);

            $user = $request->user();

            // Check if user has permission to register
            if (! $user->hasPermission('events.register')) {
                return response()->json(['success' => false, 'errors' => [__('common.permission_denied')]]);
            }

            $event = Event::findOrFail($request->input('event_id'));

            // Check if the user is already registered
            if ($event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json(['success' => false, 'errors' => [__('event.already_registered')]]);
            }

            // Attach the user to the event participants
            $event->participants()->attach($user->id);

            // Dispatch a job to send the email notification
            SendEventNotification::dispatch($event, $user);

            return response()->json([
                'success' => true,
                'message' => __('event.registered'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in registerForEvent: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    // Registration for an event
    public function register(Request $request, $eventId)
    {
        // Reuse your existing registerForEvent logic
        $request->merge(['event_id' => $eventId]);

        return $this->registerForEvent($request);
    }

    public function participants($eventId)
    {
        $event = Event::findOrFail($eventId);
        // Optional: Add role-based or permission checks here if needed
        $participants = $event->participants()->get();

        return response()->json(['success' => true, 'data' => $participants]);
    }

    public function unregister(Request $request, $eventId)
    {
        $user = $request->user();
        $event = Event::findOrFail($eventId);

        if (! $event->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'You are not registered for this event']);
        }

        $event->participants()->detach($user->id);

        return response()->json(['success' => true, 'message' => 'You have been unregistered from the event']);
    }

    public function organizerStats()
    {
        $organizerId = auth()->id();

        return response()->json([
            'total_events' => $this->model()
                ->where('organizer_id', $organizerId)
                ->count(),

            'upcoming_events' => $this->model()
                ->upcomingEvents() // ensure this matches your model's defined scope
                ->where('organizer_id', $organizerId)
                ->count(),

            'total_participants' => DB::table('event_participants')
                ->whereIn('event_id', function ($query) use ($organizerId) {
                    $query->select('id')
                        ->from('events')
                        ->where('organizer_id', $organizerId);
                })->count(),

            // Placeholder for revenue; update logic as needed
            'revenue' => 0,
        ]);
    }
}
