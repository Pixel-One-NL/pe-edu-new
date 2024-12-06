<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Course extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'eduframe_id',
        'name',
        'code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Import the data from Eduframe response
     * 
     * @param array
     */
    public function importEduframeRecord($record)
    {
        $map = [
            'eduframe_id' => 'id',
            'name'        => 'name',
            'code'        => 'code',
        ];

        Course::updateOrCreate(
            ['eduframe_id' => $record['id']],
            array_map(fn($key) => $record[$key], $map)
        );
    }

    public function generate_code(): void
    {
        $apiKey = env('EDUFRAME_ACCESS_TOKEN', null);
        $url = 'https://api.eduframe.nl/api/v1/courses/' . $this->eduframe_id;

        if (strpos($this->code, 'smartedu_') === 0) {
            return;
        }

        $data = Http::withHeader('Authorization', 'Bearer ' . $apiKey)->patch($url, [
            'code' => uniqid('smartedu_'),
        ])->json();

        $code = $data['code'];

        $this->code = $code;
        $this->save();

    }
}
