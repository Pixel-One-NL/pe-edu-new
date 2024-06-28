<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Filament\Resources\MeetingResource\RelationManagers;
use App\Filament\Resources\MeetingResource\RelationManagers\AttendancesRelationManager;
use App\Models\Meeting;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('eduframe_id')
                    ->label('Eduframe ID')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plannedCourse.course.name')
                    ->label('Cursus naam')
                    ->searchable(),

                TextColumn::make('plannedCourse.course.code')
                    ->label('Cursus code')
                    ->searchable(),

                TextColumn::make('start_date_time')
                    ->label('Start datum')
                    ->dateTime('d-m-Y'),

                TextColumn::make('end_date_time')
                    ->label('Eind datum')
                    ->dateTime('d-m-Y'),

                IconColumn::make('isExported')
                    ->label('Geëxporteerd')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
            ])
            ->filters([
                Filter::make('only_past')
                    ->label('Alleen verleden meetings')
                    ->toggle()
                    ->default(true)
                    ->query(fn (Builder $query): Builder => $query->where('end_date_time', '<', now())),
            ])
            ->actions([
                Action::make('export')
                    ->label('Exporteer')
                    ->action(fn ($record) => dispatch(new \App\Jobs\ExportMeeting($record)))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->attendances->count() > 0),
                    ])
            ->bulkActions([

            ])
            ->defaultSort('start_date_time', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
}
