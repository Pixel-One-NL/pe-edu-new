<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'eduframe_id',
        'meeting_eduframe_id',
        'enrollment_eduframe_id',
        'state',
        'comment',
        'exported',
        'exported_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exported'    => 'boolean',
        'exported_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Get the meeting that owns the attendance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the enrollment that owns the attendance.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_eduframe_id', 'eduframe_id');
    }

    /**
     * Import the data from Eduframe response
     * 
     * @param array
     */
    public function importEduframeRecord($record)
    {
        $map = [
            'eduframe_id'            => 'id',
            'meeting_eduframe_id'    => 'meeting_id',
            'enrollment_eduframe_id' => 'enrollment_id',
            'state'                  => 'state',
            'comment'                => 'comment',
        ];

        Attendance::updateOrCreate(
            ['eduframe_id' => $record['id']],
            array_map(fn($key) => $record[$key], $map)
        );
    }
}
