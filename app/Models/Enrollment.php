<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'eduframe_id',
        'planned_course_eduframe_id',
        'user_eduframe_id',
        'start_date',
        'end_date',
        'status',
        'graduation_state',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the planned course that owns the enrollment.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plannedCourse()
    {
        return $this->belongsTo(PlannedCourse::class, 'planned_course_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the user that owns the enrollment.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(EduframeUser::class, 'user_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the attendance for the enrollment.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'enrollment_eduframe_id', 'eduframe_id');
    }

    /**
     * Import the data from Eduframe response
     * 
     * @param array
     */
    public function importEduframeRecord($record)
    {
        $map = [
            'eduframe_id'                => 'id',
            'planned_course_eduframe_id' => 'planned_course_id',
            'user_eduframe_id'           => 'student_id',
            'start_date'                 => 'start_date',
            'end_date'                   => 'end_date',
            'status'                     => 'status',
            'graduation_state'           => 'graduation_state',
        ];
        
        Enrollment::updateOrCreate(
            ['eduframe_id' => $record['id']],
            array_map(fn($key) => $record[$key], $map)
        );
    }
}
