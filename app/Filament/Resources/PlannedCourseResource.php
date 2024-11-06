<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlannedCourseResource\Pages;
use App\Filament\Resources\PlannedCourseResource\RelationManagers;
use App\Models\EduframeUser;
use App\Models\PlannedCourse;
use Creagia\FilamentCodeField\CodeField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

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

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Placeholder::make('exported_successfully')
                        ->content(fn(PlannedCourse $record) => $record->exported_successfully ? 'Ja' : 'Nee'),
                    ])
                    ->columnSpan(2),

                CodeField::make('response')
                    ->columnSpan(1)
                    ->setLanguage(CodeField::XML)
                    ->withLineNumbers()
                    ->xmlField(),

                Forms\Components\Placeholder::make('exported_users')
                    ->content(function(Get $get) {
                        $response = $get('response');

                        if(!$response) {
                            return new HtmlString(View::make('placeholders.exported-users')->with('users', [])->render());
                        }

                        $failedRizivNumbers = [];

                        // Transform the response into xml
                        $dom = new \DOMDocument;
                        $dom->preserveWhiteSpace = false;
                        $dom->formatOutput = true;
                        $dom->loadXML($response);

                        foreach($dom->getElementsByTagName('Error') as $error) {
                            $errorMessage = $error->getElementsByTagName('errorMsg')->item(0)->nodeValue;

                            // Check if the string contains 'ERROR1050'
                            if(strpos($errorMessage, 'ERROR1050') !== false) {
                                preg_match('/\d{5}/', $error->nodeValue, $matches);

                                if(count($matches)) {
                                    $failedRizivNumbers[] = $matches[0];
                                }
                            }
                        }

                        $eduframeUsers = EduframeUser::getByRizivNumbers($failedRizivNumbers)->get();

                        return new HtmlString(View::make('placeholders.exported-users')->with('users', $eduframeUsers)->render());
                    }),
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
