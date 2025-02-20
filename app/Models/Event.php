<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Event extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * Event statuses.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_COMPLETED = 'completed';

    /**
     * Event visibilities.
     */
    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_GROUP = 'group';

    /**
     * Fillable fields for mass assignment.
     */
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'organizer_id',
        'location',
        'max_participants',
        'image',
        'status',
        'visibility', // Add visibility field
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'visibility' => self::VISIBILITY_PUBLIC, // Default visibility to public
    ];

    /**
     * Appended attributes to include in JSON responses.
     */
    protected $appends = ['participant_count'];

    /**
     * Dates that should be mutated to Carbon instances.
     */
    protected $dates = ['deleted_at'];

    /**
     * Relationship: Event organizer.
     */
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * Relationship: Event participants.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_participants');
    }

    /**
     * Scope: Filter published events.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope: Filter draft events.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Filter canceled events.
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', self::STATUS_CANCELED);
    }

    /**
     * Scope: Filter completed events.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Filter upcoming events.
     */
    public function scopeUpcomingEvents($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * Scope: Filter events organized by a specific user.
     */
    public function scopeOrganizedBy($query, $userId)
    {
        return $query->where('organizer_id', $userId);
    }

    /**
     * Accessor: Get the participant count.
     */
    public function getParticipantCountAttribute()
    {
        return $this->participants()->count();
    }

    /**
     * Accessor: Get the event visibility.
     */
    /**
     * Scope: Filter events that are visible to the user.
     */
    public function scopeVisible($query, $user = null)
    {
        return $query->where(function ($query) use ($user) {
            $query->where('visibility', self::VISIBILITY_PUBLIC) // Public events are accessible to all
                ->orWhere('visibility', self::VISIBILITY_GROUP) // Group events are visible to users in that group
                ->whereHas('groups', function ($query) use ($user) {
                    $query->whereHas('participants', function ($query) use ($user) {
                        $query->where('user_id', $user->id); // Check if the user is part of the group
                    });
                })
                ->orWhere(function ($query) use ($user) {
                    $query->where('visibility', self::VISIBILITY_PRIVATE) // Private events should be visible based on permissions
                        ->where('organizer_id', $user->id); // Only the organizer can view private events
                });
        });
    }

    /**
     * Boot method to handle model events.
     */
    protected static function booted()
    {
        parent::booted();

        // Assign permissions when an event is created.
        static::created(function ($event) {
            $organizer = $event->organizer;
            if ($organizer) {
                $organizer->givePermission('events.'.$event->id.'.read');
                $organizer->givePermission('events.'.$event->id.'.update');
                $organizer->givePermission('events.'.$event->id.'.delete');
            }
        });

        // Clean up permissions when an event is deleted.
        static::deleted(function ($event) {
            DB::table('permissions')
                ->where('name', 'like', 'events.'.$event->id.'.%')
                ->delete();
        });
    }

    /**
     * Validation rules for creating or updating an event.
     *
     * @param  int|null  $id
     * @return array
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
            'image' => 'nullable|url',
            'status' => 'required|in:draft,published,canceled,completed',
            'visibility' => 'required|in:public,private,group', // Add validation for visibility
        ];
    }
}
