<?php

namespace App\Filament\Resources\PlannedCourseResource\Pages;

use App\Filament\Resources\PlannedCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlannedCourses extends ListRecords
{
    protected static string $resource = PlannedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
