<?php

namespace App\Livewire;

use App\Jobs\MasterImport;
use App\Models\JobStatus;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class RunMasterImport extends Widget
{
    protected static string $view = 'livewire.run-master-import';

    public $jobStatus = null;
    public $lastRun   = null;

    public function mount() {
        $this->getJobStatus();
    }

    #[On('master-import-status-changed')]
    public function getJobStatus()
    {
        $jobStatus       = JobStatus::where('job_id', 'master-import')->first();
        $this->lastRun   = $jobStatus ? $jobStatus->updated_at : null;
        $this->jobStatus = $jobStatus ? $jobStatus->status : null;
    }

    public function runImport()
    {
        JobStatus::updateOrCreate(['job_id' => 'master-import'], ['status' => 'processing']);

        // Run the import job here
        dispatch(new MasterImport());

        $this->getJobStatus();
    }
}
