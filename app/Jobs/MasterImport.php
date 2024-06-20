<?php

namespace App\Jobs;

use App\Models\JobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MasterImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Define the per page for each resource
     * 
     * @var array[<string, int>]
     */
    protected $perPage = [
        'attendances'     => 100,
        'meetings'        => 100,
        'courses'         => 100,
        'planned_courses' => 100,
        'users'           => 100,
        'enrollments'     => 25,
    ];

    /**
     * Define the model for each resource
     * 
     * @var array[<string, Class>]
     */
    protected $model = [
        'attendances'     => \App\Models\Attendance::class,
        'meetings'        => \App\Models\Meeting::class,
        'courses'         => \App\Models\Course::class,
        'planned_courses' => \App\Models\PlannedCourse::class,
        'users'           => \App\Models\EduframeUser::class,
        'enrollments'     => \App\Models\Enrollment::class,
    ];

    /**
     * The current resource's ids that are being imported, used to delete records that are not in the response anymore
     * 
     * @var array
     */
    protected $currentIds = [];

    /**
     * Define the resource to import
     * 
     * @var string
     */
    protected $resource;

    /**
     * Define the page to import
     * 
     * @var int
     */
    protected $page;

    /**
     * Create a new job instance.
     */
    public function __construct($resource = null, $page = 1, $currentIds = [])
    {
        $this->resource   = $resource ?: array_key_first($this->perPage);
        $this->page       = $page;
        $this->currentIds = $currentIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Running import for ' . $this->resource . ' on page ' . $this->page);

            $apiKey = env('EDUFRAME_ACCESS_TOKEN', null);
            $url    = 'https://api.eduframe.nl/api/v1/' . $this->resource . '?page=' . $this->page . '&per_page=' . $this->perPage[$this->resource];

            if(!$apiKey) {
                JobStatus::updateOrCreate(['job_id' => 'master-import'], ['status' => 'failed']);
                return;
            }

            $data        = Http::withHeader('Authorization', 'Bearer ' . $apiKey)->get($url)->json();
            $receivedIds = array_column($data, 'id');

            foreach ($data as $record) {
                $model = new $this->model[$this->resource];
                $model->importEduframeRecord($record);
            }

            $newCurrentIds = array_merge($this->currentIds, $receivedIds);

            if(count($data) == $this->perPage[$this->resource]) {
                dispatch(new MasterImport($this->resource, $this->page + 1, $newCurrentIds));
            } elseif(count($data) < $this->perPage[$this->resource] && array_key_last($this->perPage) != $this->resource) {
                $nextResource = array_keys($this->perPage)[array_search($this->resource, array_keys($this->perPage)) + 1];
                dispatch(new MasterImport($nextResource, 1, []));
            } else {
                JobStatus::updateOrCreate(['job_id' => 'master-import'], ['status' => 'completed']);
            }
        } catch (\Exception $e) {
            Log::error('Error importing ' . $this->resource . ' on page ' . $this->page . ': ' . $e->getMessage());
            Log::debug('Stacktrace:', [$e]);
            JobStatus::updateOrCreate(['job_id' => 'master-import'], ['status' => 'failed']);
        }
    }
}
