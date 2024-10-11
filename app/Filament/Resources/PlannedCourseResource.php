<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlannedCourseResource\Pages;
use App\Filament\Resources\PlannedCourseResource\RelationManagers;
use App\Models\PlannedCourse;
use Creagia\FilamentCodeField\CodeField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlannedCourseResource extends Resource
{
    protected static ?string $model = PlannedCourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('pe_course_id'),
                Forms\Components\TextInput::make('edition_id'),

                Forms\Components\Placeholder::make('exported_successfully')
                    ->content(fn(PlannedCourse $record) => $record->exported_successfully ? 'Ja' : 'Nee'),

                CodeField::make('response')
                    ->columnSpan(2)
                    ->setLanguage(CodeField::XML)
                    ->withLineNumbers()
                    ->xmlField(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Naam')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('d-m-Y'),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('d-m-Y'),
            ])
            ->filters([
                TernaryFilter::make('start_date_to_now')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->where('start_date', '<', now()),
                        false: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
//                Tables\Actions\Action::make('export')
//                    ->action(function($record) {
//                        dispatch(new \App\Jobs\ExportPlannedCourse($record));
//                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MeetingsRelationManager::class,
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
