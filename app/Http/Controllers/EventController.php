<?php

namespace App\Http\Controllers;

use App\Jobs\SendEventNotification;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Log;
use Request;

class EventController extends CrudController
{
    protected $table = 'events';

    protected $modelClass = Event::class;

    // protected $restricted = ['delete'];

    protected function getTable()
    {
        return $this->table;
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }

    protected function getReadAllQuery(): Builder
    {
        // Order event by alphabetic name
        return $this->model()->orderBy('name', 'asc');
    }

    protected function afterCreateOne() {}

    protected function beforeDeleteOne() {}

    // register to an event and send notification via mail
    protected function registerForEvent(Request $request)
    {
        try {
            $user = $request->user();
            $eventId = $request->input('event_id');
            $event = Event::findOrFail($eventId);
            // Attach the user to the event participants
            $event->participants()->attach($user->id);
            // Dispatch a job to send the email notification
            SendEventNotification::dispatch($event, $user);

            return response()->json(
                [
                    'success' => true,
                    'message' => __('event.registered'),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error in registerForEvent: '.$e->getMessage());

            return response()->json(['success' => false, 'errors' => [__('common.unexpected_error')]]);
        }
    }
}
