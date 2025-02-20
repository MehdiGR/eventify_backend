<?php

namespace App\Http\Controllers;

use App\Events\NewEventCreated;
use App\Jobs\SendEventNotification;
use App\Models\Event;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventController extends CrudController
{
    protected $table = 'events';

    protected $modelClass = Event::class;

    public function __construct()
    {
        // Optionally modify restrictions for readAll and readOne
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

    /**
     * READ ALL events.
     * Supports public filtering by organizer_id.
     */
    public function readAll(Request $request)
    {
        try {
            $query = Event::query();

            // Allow filtering by organizer_id if provided
            if ($request->has('organizer_id')) {
                $query->where('organizer_id', $request->organizer_id);
            }

            // Order by start date (most recent first)
            $events = $query->orderBy('start_date', 'desc')->paginate($request->input('per_page', 50));

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
        } catch (\Exception $e) {
            Log::error('EventController readAll error: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    /**
     * After creating an event, assign update/delete permissions,
     * dispatch the NewEventCreated event and schedule notifications.
     */
    protected function afterCreateOne($event, Request $request)
    {
        try {
            DB::afterCommit(function () use ($event, $request) {
                $user = $request->user();
                if (! $user) {
                    Log::error('User not found in request.');

                    return;
                }
                $user->givePermission("events.{$event->id}.update_own");
                $user->givePermission("events.{$event->id}.delete_own");
                event(new NewEventCreated($event));
                SendEventNotification::dispatch($event, $user);
            });
        } catch (\Exception $e) {
            Log::error('Error processing event creation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.file_upload_error')],
            ]);
        }
    }

    /**
     * After updating an event, notify participants if key details change.
     */
    protected function afterUpdateOne($event, Request $request)
    {
        if (isset($request->start_date) || isset($request->location)) {
            SendEventNotification::dispatch($event, $request->user(), 'update');
        }
    }

    /**
     * Participant self-registration.
     */
    protected function registerForEvent(Request $request)
    {
        try {
            $request->validate([
                'event_id' => 'required|exists:events,id',
            ]);
            $user = $request->user();
            if (! $user->hasPermission('events.register')) {
                return response()->json(['success' => false, 'errors' => [__('common.permission_denied')]]);
            }
            $event = Event::findOrFail($request->input('event_id'));
            if ($event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json(['success' => false, 'errors' => [__('event.already_registered')]]);
            }
            $event->participants()->attach($user->id);
            SendEventNotification::dispatch($event, $user);

            return response()->json(['success' => true, 'message' => __('event.registered')]);
        } catch (\Exception $e) {
            Log::error('Error in registerForEvent: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    /**
     * Endpoint for participant self-registration.
     */
    public function register(Request $request, $eventId)
    {
        $request->merge(['event_id' => $eventId]);

        return $this->registerForEvent($request);
    }

    /**
     * Retrieve the list of participants for an event.
     * Accessible by both organizers and participants.
     */
    public function participants($eventId)
    {
        $event = Event::findOrFail($eventId);
        $participants = $event->participants()->get();

        return response()->json(['success' => true, 'data' => $participants]);
    }

    /**
     * Endpoint for participant self-unregistration.
     */
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

    /**
     * Organizer manually adds a participant to an event.
     */
    public function addParticipant(Request $request, $eventId)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);
            $organizer = $request->user();
            $event = Event::findOrFail($eventId);
            // Ensure the authenticated user is the event organizer.
            if ($event->organizer_id !== $organizer->id) {
                return response()->json(['success' => false, 'message' => __('common.permission_denied')]);
            }
            if ($event->participants()->where('user_id', $request->input('user_id'))->exists()) {
                return response()->json(['success' => false, 'message' => __('event.already_registered')]);
            }
            $event->participants()->attach($request->input('user_id'));

            return response()->json(['success' => true, 'message' => __('event.participant_added')]);
        } catch (\Exception $e) {
            Log::error('Error in addParticipant: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    /**
     * Organizer removes a participant from an event.
     */
    public function removeParticipant($eventId, $userId)
    {
        try {
            $organizerId = Auth::id();
            $event = Event::findOrFail($eventId);
            if ($event->organizer_id !== $organizerId) {
                return response()->json(['success' => false, 'message' => __('common.permission_denied')]);
            }
            if (! $event->participants()->where('user_id', $userId)->exists()) {
                return response()->json(['success' => false, 'message' => __('event.participant_not_found')]);
            }
            $event->participants()->detach($userId);

            return response()->json(['success' => true, 'message' => __('event.participant_removed')]);
        } catch (\Exception $e) {
            Log::error('Error in removeParticipant: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }

    /**
     * Returns statistics for the organizer's dashboard.
     */
    public function organizerStats()
    {
        $organizerId = Auth::id();

        return response()->json([
            'total_events' => $this->model()
                ->where('organizer_id', $organizerId)
                ->count(),
            'upcoming_events' => $this->model()
                ->upcomingEvents()
                ->where('organizer_id', $organizerId)
                ->count(),
            'total_participants' => DB::table('event_participants')
                ->whereIn('event_id', function ($query) use ($organizerId) {
                    $query->select('id')
                        ->from('events')
                        ->where('organizer_id', $organizerId);
                })->count(),
            'revenue' => 0, // Placeholder for revenue logic
        ]);
    }
}
