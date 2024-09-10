<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlannedCourseResource\Pages;
use App\Filament\Resources\PlannedCourseResource\RelationManagers;
use App\Models\Attendance;
use App\Models\EduframeUser;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\PlannedCourse;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlannedCourseResource extends Resource
{
    protected static ?string $model = PlannedCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('response')
                    ->label('Response')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('eduframe_id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('course.name')
                    ->words(4)
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollments.count')
                    ->label('Enrollments')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('export_to_pe')
                    ->label('Export')
                    ->requiresConfirmation()
                    ->action(function(PlannedCourse $plannedCourse) {
                        $userIds = [];
                        
                        $plannedCourse->meetings->each(function(Meeting $meeting) use (&$userIds) {
                            $meeting->attendances->each(function(Attendance $attendance) use (&$userIds, ) {
                                if($attendance->state == 'attended') {
                                    if(!isset($userIds[$attendance->enrollment->user->eduframe_id])) {
                                        $userIds[$attendance->enrollment->user->eduframe_id] = 1;
                                    } else {
                                        $userIds[$attendance->enrollment->user->eduframe_id]++;
                                    }
                                }
                            });
                        });

                        foreach($userIds as $userId => $attendanceCount) {
                            if($attendanceCount !== $plannedCourse->meetings->count()) {
                                unset($userIds[$userId]);
                            }
                        }

                        $userIds = array_keys($userIds);

                        dispatch(new \App\Jobs\ExportPlannedCourse($plannedCourse, $userIds));
                    }),
            ])
            ->bulkActions([
                // 
            ])
            ->defaultSort('start_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDate('end_date', '<=', now()->toDateString()));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlannedCourses::route('/'),
            'create' => Pages\CreatePlannedCourse::route('/create'),
            'edit' => Pages\EditPlannedCourse::route('/{record}/edit'),
        ];
    }
}
