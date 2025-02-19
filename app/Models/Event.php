<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Event extends BaseModel
{
    use HasFactory;

    // Fillable fields for mass assignment
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'organizer_id',
        'location',
        'max_participants',
        'image',
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

    // public function ticketTypes()
    // {
    //     return $this->hasMany(TicketType::class);
    // }
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
    // In your HasDataTables trait or Event model
    //     public function scopeDataTable($query, $params)
    // {
    //     // Only check permissions if user exists
    //     if ($params->user) {
    //         if (!$params->user->allTableReadPermissions($this->getTable())) {
    //             throw new PermissionException();
    //         }
    //     }

    //     // Keep existing sorting - works for all users
    //     foreach ($params->order as $order) {
    //         $query->orderBy($order['column'], $order['direction']);
    //     }

    //     // Keep existing filtering - works for all users
    //     foreach ($params->filter as $filter) {
    //         $query->where($filter['column'], $filter['operator'], $filter['value']);
    //     }

    //     return $query;
    // }
    /**
     * Validation Rules
     */
    public static function rules($id = null)
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after:start_date',
            'organizer_id' => 'required|exists:users,id',
            'location' => 'required|string|max:255',
            'max_participants' => 'required|integer|min:1',
            'image' => 'nullable|url', // Allows optional URL strings
        ];
    }
}
