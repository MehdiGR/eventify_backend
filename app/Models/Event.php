<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    // Fillable fields for mass assignment
    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'organizer_id'];

    // Append custom attributes to JSON responses
    protected $appends = ['participant_count'];

    // Enable soft deletes
    use SoftDeletes;

    // Define the date fields for soft deletes
    protected $dates = ['deleted_at'];

    /**
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
            $permissions = Permission::where('name', 'like', 'events.'.$event->id.'.%')->get();
            DB::table('users_permissions')->whereIn('permission_id', $permissions->pluck('id'))->delete();
            Permission::destroy($permissions->pluck('id'));
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
    public function UpcomingEvents($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function OrganizedBy($query, $userId)
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
        ];
    }
}
