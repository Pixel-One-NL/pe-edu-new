<?php

namespace App\Models;

use App\Jobs\ConnectEduframeUserToAttendance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

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
     * @return BelongsTo
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the enrollment that owns the attendance.
     *
     * @return BelongsTo
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the user that belongs to the attendance (through enrollment)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(EduframeUser::class, Enrollment::class, 'eduframe_id', 'eduframe_id', 'enrollment_eduframe_id', 'user_eduframe_id');
    }

    /**
     * Import the data from Eduframe response
     *
     * @param array $record
     * @throws ConnectionException
     */
    public function importEduframeRecord(array $record): void
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

//        dispatch(new ConnectEduframeUserToAttendance($attendance));
    }
}
