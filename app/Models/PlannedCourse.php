<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'response',
        'edition_id',
        'exported_at',
        'response',
        'pe_course_id',
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
        'exported_at' => 'datetime',
        'exported_successfully' => 'boolean',
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
     * @return HasMany
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'planned_course_eduframe_id', 'eduframe_id');
    }

    /**
     * Get the meetings for the planned course.
     *
     * @return HasMany
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'planned_course_eduframe_id', 'eduframe_id');
    }

    public function getResponseAttribute($value): false|string
    {
        if(!$value) return '';

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($value);
        return $dom->saveXML();
    }

    public function getExportedSuccessfullyAttribute(): bool
    {
        if(!$this->response) return false;

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->response);

        if(!$dom->getElementsByTagName('total_rows')->length) return false;
        if(!$dom->getElementsByTagName('accepted_rows')->length) return false;

        $totalRows    = $dom->getElementsByTagName('total_rows')->item(0)->nodeValue;
        $acceptedRows = $dom->getElementsByTagName('accepted_rows')->item(0)->nodeValue;

        if($totalRows == 1) return $totalRows == $acceptedRows;
        if($totalRows == 0) return false;

        if($totalRows > 1) {
            $acceptedRows = $dom->getElementsByTagName('Accepted');
            foreach($acceptedRows as $acceptedRow) {
                $personId = $acceptedRow->getElementsByTagName('person')->item(0)->nodeValue;

                $externalPersonId = htmlspecialchars($this->user->getRIZIV());
                $externalPersonId = str_replace(['-', ' '], '', $externalPersonId);
                $externalPersonId = substr($externalPersonId, 1);
                $externalPersonId = substr($externalPersonId, 0, 5);

                if($personId == $externalPersonId) return true;
            }
        }

        return false;
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
