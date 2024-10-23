<?php

namespace App\Filament\Resources\PlannedCourseResource\Pages;

use App\Filament\Resources\PlannedCourseResource;
use App\Jobs\ExportPlannedCourse;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlannedCourse extends EditRecord
{
    protected static string $resource = PlannedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make('export_planned_course')
                ->label('Export')
//                ->requiresConfirmation()
                ->action(function() {
                    dispatch_sync(new ExportPlannedCourse($this->record));

                    $this->fillForm();
                }),
        ];
    }
}
