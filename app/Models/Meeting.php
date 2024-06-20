<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'eduframe_id',
        'start_date_time',
        'end_date_time',
        'planned_course_eduframe_id',
        'description',
        'pe_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date_time' => 'datetime',
        'end_date_time'   => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Get the planned course that owns the meeting.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plannedCourse()
    {
        return $this->belongsTo(PlannedCourse::class, 'planned_course_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the attendances for the meeting.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'meeting_eduframe_id', 'eduframe_id');
    }

    /**
     * Check if the meeting is exported
     * 
     * @return bool
     */
    public function getIsExportedAttribute()
    {
        return $this->attendances->where('exported', true)->count() > 0;
    }

    /**
     * Import the data from Eduframe response
     * 
     * @param array
     */
    public function importEduframeRecord($record)
    {
        $map = [
            'name'                       => 'name',
            'eduframe_id'                => 'id',
            'start_date_time'            => 'start_date_time',
            'end_date_time'              => 'end_date_time',
            'planned_course_eduframe_id' => 'planned_course_id',
            'description'                => 'description',
        ];

        Meeting::updateOrCreate(
            ['eduframe_id' => $record['id']],
            array_map(fn($key) => $record[$key], $map)
        );
    }
}
