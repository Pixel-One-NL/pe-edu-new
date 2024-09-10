<?php

namespace App\Filament\Resources\PlannedCourseResource\Pages;

use App\Filament\Resources\PlannedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlannedCourse extends EditRecord
{
    protected static string $resource = PlannedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
