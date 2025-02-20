<?php

namespace App\Http\Controllers;

use App\Events\NewEventCreated;
use App\Events\PublishedEventNotification;
use App\Events\RegistrationNotification;
use App\Jobs\SendEventNotification;
use App\Mail\ParticipantActionMail;
use App\Models\Event;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mail;

class EventController extends CrudController
{
    protected $table = 'events';

    protected $modelClass = Event::class;

    public function __construct()
    {
        // Make read operations public
        $this->restricted = array_diff($this->restricted, ['read_all', 'read_one']);
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

            if ($request->has('organizer_id')) {
                $query->where('organizer_id', $request->organizer_id);
            }

            $events = $query->orderBy('start_date', 'desc')
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
        } catch (\Exception $e) {
            Log::error('EventController readAll error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
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
                // event(new NewEventCreated($event));
                // SendEventNotification::dispatch($event, $user);
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
     * Visitors can access the register page, and if not logged in the frontend shows a modal.
     */
    public function register(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'errors' => ['You must be logged in to register.'],
                ]);
            }
            $event = Event::findOrFail($id);
            if ($event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors' => [__('event.already_registered')],
                ]);
            }
            $event->participants()->attach($user->id);

            // Notify the organizer about the new registration
            $organizer = User::find($event->organizer_id);
            event(new RegistrationNotification($event, $user, 'register'));
            Mail::to($organizer->email)
                ->queue(new ParticipantActionMail($event, $user, 'register'));

            return response()->json([
                'success' => true,
                'message' => __('event.registered'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in register: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
        }
    }

    /**
     * Participant self-unregistration.
     */
    public function unregister(Request $request, $id)
    {
        try {
            $user = $request->user();
            $event = Event::findOrFail($id);
            if (! $event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered for this event',
                ]);
            }
            $event->participants()->detach($user->id);

            // Notify the organizer about the unregistration
            $organizer = User::find($event->organizer_id);
            event(new RegistrationNotification($event, $user, 'unregister'));
            Mail::to($organizer->email)
                ->queue(new ParticipantActionMail($event, $user, 'unregister'));

            return response()->json([
                'success' => true,
                'message' => 'You have been unregistered from the event',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in unregister: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
        }
    }

    /**
     * Retrieve the list of participants for an event.
     */
    public function participants($id)
    {
        try {
            $event = Event::findOrFail($id);
            $participants = $event->participants()->get();

            return response()->json([
                'success' => true,
                'data' => $participants,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in participants: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
        }
    }

    /**
     * Retrieve the events a participant is registered for.
     */
    public function participantsEvents(Request $request)
    {
        try {
            $user = $request->user();
            $events = $user->events()->orderBy('start_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $events,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in participantsEvents: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
        }
    }

    /**
     * Retrieve events organized by the authenticated organizer.
     */
    public function organizerEvents(Request $request)
    {
        try {
            $organizerId = Auth::id();
            $events = $this->model()->where('organizer_id', $organizerId)
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
        } catch (\Exception $e) {
            Log::error('Error in organizerEvents: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
        }
    }

    /**
     * Organizer-triggered event notifications.
     * This endpoint is called when the organizer clicks the push button in the frontend.
     */
    public function sendEventNotifications(Request $request, $id)
    {
        try {
            $user = $request->user();
            $event = Event::findOrFail($id);
            if ($event->organizer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('common.permission_denied'),
                ]);
            }
            event(new PublishedEventNotification($event, $user));

            return response()->json([
                'success' => true,
                'message' => __('event.notifications_sent'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending event notifications: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => [__('common.unexpected_error')],
            ]);
        }
    }

    protected function model()
    {
        return new ($this->modelClass)();
    }
}
