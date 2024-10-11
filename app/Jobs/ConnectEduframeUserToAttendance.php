<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\EduframeUser;
use App\Models\JobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConnectEduframeUserToAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Attendance $attendance,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $apiKey = env('EDUFRAME_ACCESS_TOKEN', null);
        $url = 'https://api.eduframe.nl/api/v1/enrollments/' . $this->attendance->enrollment_eduframe_id;

        $data = Http::withHeader('Authorization', 'Bearer ' . $apiKey)->get($url)->json();

        if (isset($data['student_id'])) {
            Log::info('Connecting user to attendance for enrollment ' . $this->attendance->enrollment_eduframe_id . ' with user ' . $data['student_id']);
            $eduframeUser = EduframeUser::where('eduframe_id', $data['student_id'])->first();
            $this->attendance->user()->associate($eduframeUser);
        }
    }
}
