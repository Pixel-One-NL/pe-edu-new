<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('eduframe_id')
                    ->label('Eduframe ID')
                    ->disabled(),

                Forms\Components\TextInput::make('meeting_eduframe_id')
                    ->label('Meeting Eduframe ID')
                    ->disabled(),

                Forms\Components\TextInput::make('enrollment_eduframe_id')
                    ->label('Enrollment Eduframe ID')
                    ->disabled(),

                Forms\Components\Select::make('state')
                    ->label('State')
                    ->options([
                        'present' => 'Present',
                        'absent'  => 'Absent',
                        'excused' => 'Excused',
                    ])
                    ->disabled(),

                Forms\Components\Textarea::make('comment')
                    ->label('Comment')
                    ->nullable()
                    ->disabled(),

                Forms\Components\Checkbox::make('exported')
                    ->label('Exported')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('exported_at')
                    ->label('Exported At')
                    ->nullable()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('eduframe_id')
                    ->label('Eduframe ID'),
            ])
            ->filters([
                //
            ])
            ->actions([
                
            ])
            ->bulkActions([
                
            ]);
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
