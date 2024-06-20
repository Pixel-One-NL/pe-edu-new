<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlannedCourse extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_eduframe_id',
        'type',
        'start_date',
        'end_date',
        'duration_in_days',
        'status',
        'eduframe_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * Get the course that owns the planned course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the enrollments for the planned course.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'planned_course_eduframe_id', 'eduframe_id');
    }

    /**
     * Import the data from Eduframe response
     * 
     * @param array
     */
    public function importEduframeRecord($record)
    {
        $map = [
            'eduframe_id'        => 'id',
            'course_eduframe_id' => 'course_id',
            'type'               => 'type',
            'start_date'         => 'start_date',
            'end_date'           => 'end_date',
            'duration_in_days'   => 'duration_in_days',
            'status'             => 'status',
        ];

        PlannedCourse::updateOrCreate(
            ['eduframe_id' => $record['id']],
            array_map(fn($key) => $record[$key], $map)
        );
    }
}
