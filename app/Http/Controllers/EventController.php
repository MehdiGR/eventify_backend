<?php

namespace App\Http\Controllers;

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
                if ($user->hasRole('ADMIN') || $user->id == $request->organizer_id) {
                    $this->restricted = array_diff($this->restricted, ['read_all']);
                }
            }

            return parent::readAll($request);
        } catch (\Exception $e) {
            Log::error('EventController readAll error: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

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

    public function updateOne($id, Request $request)
    {
        $event = $this->model()->find($id);

        // Check Global "events.update" + Ownership "events.{id}.update_own"
        if (! $request->user()->hasAnyPermission(['events.update', "events.{$id}.update_own"])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return parent::updateOne($id, $request);
    }

    public function deleteOne($id, Request $request)
    {
        $event = $this->model()->find($id);

        // Check: Global "events.delete" + Ownership "events.{id}.delete_own"
        if (! $request->user()->hasAnyPermission(['events.delete', "events.{$id}.delete_own"])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return parent::deleteOne($id, $request);
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

    // EventController.php

    public function organizerStats()
    {
        $organizerId = auth()->id();

        return response()->json([
            'total_events' => $this->model()
                ->where('organizer_id', $organizerId)
                ->count(),

            'upcoming_events' => $this->model()
                ->upcoming()
                ->where('organizer_id', $organizerId)
                ->count(),

            'participants' => DB::table('event_participants')
                ->whereIn('event_id', function ($query) use ($organizerId) {
                    $query->select('id')
                        ->from('events')
                        ->where('organizer_id', $organizerId);
                })->count(),
        ]);
    }
}
