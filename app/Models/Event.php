<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Event extends BaseModel
{
    // Fillable fields for mass assignment
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'organizer_id',
        'location',
        'max_participants',
    ];

    // Append custom attributes to JSON responses
    protected $appends = ['participant_count'];

    // Enable soft deletes
    use SoftDeletes;

    // Define the date fields for soft deletes
    protected $dates = ['deleted_at'];

    /**,        'max_participants', // New field: Maximum number of participants
     *
     * Relationships
     */
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_participants');
    }

    /**
     * Booted method for event lifecycle hooks
     */
    protected static function booted()
    {
        parent::booted();

        // Assign permissions when an event is created
        static::created(function ($event) {
            $organizer = $event->organizer; // Get the organizer of the event
            if ($organizer) {
                $organizer->givePermission('events.'.$event->id.'.read');
                $organizer->givePermission('events.'.$event->id.'.update');
                $organizer->givePermission('events.'.$event->id.'.delete');
            }
        });

        // Clean up permissions when an event is deleted
        static::deleted(function ($event) {
            DB::table('permissions')
                ->where('name', 'like', 'events.'.$event->id.'.%')
                ->delete();
        });
    }

    /**
     * Custom Accessor for participant count
     */
    public function getParticipantCountAttribute()
    {
        return $this->participants()->count();
    }

    /**
     * Query Scopes
     */
    public function scopeUpcomingEvents($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeOrganizedBy($query, $userId)
    {
        return $query->where('organizer_id', $userId);
    }

    /**
     * Validation Rules
     */
    public static function rules($id = null)
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string|max:255',
            'max_participants' => 'required|integer|min:1',
        ];
    }
}
