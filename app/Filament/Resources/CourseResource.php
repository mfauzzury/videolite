<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'VideoLite';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Course Details')
                    ->tabs([
                        // Tab 1: Basic Information
                        Forms\Components\Tabs\Tab::make('Details')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('URL-friendly version of the title'),
                                Forms\Components\TextInput::make('subtitle')
                                    ->maxLength(500)
                                    ->helperText('Short description displayed under the title'),
                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                                    ->helperText('Detailed course description'),
                                SpatieMediaLibraryFileUpload::make('thumbnail')
                                    ->collection('thumbnail')
                                    ->image()
                                    ->maxSize(5120)
                                    ->helperText('Course thumbnail image (max 5MB)'),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('slug')
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                        Forms\Components\Select::make('instructor_id')
                                            ->label('Instructor')
                                            ->relationship('instructor', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->default(fn () => auth()->id()),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('school_year')
                                            ->options([
                                                'Form 1' => 'Form 1',
                                                'Form 2' => 'Form 2',
                                                'Form 3' => 'Form 3',
                                                'Form 4' => 'Form 4',
                                                'Form 5' => 'Form 5',
                                                'Form 6' => 'Form 6',
                                            ])
                                            ->searchable()
                                            ->helperText('Target school year/grade'),
                                        Forms\Components\TextInput::make('topic')
                                            ->maxLength(255)
                                            ->placeholder('e.g., Algebra, Geometry, Calculus')
                                            ->helperText('Mathematics topic'),
                                        Forms\Components\Select::make('level')
                                            ->required()
                                            ->options([
                                                'beginner' => 'Beginner',
                                                'intermediate' => 'Intermediate',
                                                'advanced' => 'Advanced',
                                            ])
                                            ->default('beginner'),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->required()
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                                'archived' => 'Archived',
                                            ])
                                            ->default('draft'),
                                        Forms\Components\DateTimePicker::make('published_at')
                                            ->label('Publish Date')
                                            ->helperText('Leave empty to publish immediately'),
                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured Course')
                                            ->default(false),
                                    ]),
                            ]),

                        // Tab 2: Pricing
                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('price')
                                            ->required()
                                            ->numeric()
                                            ->default(0.00)
                                            ->prefix('RM')
                                            ->helperText('Course price in Malaysian Ringgit'),
                                        Forms\Components\TextInput::make('compare_at_price')
                                            ->numeric()
                                            ->prefix('RM')
                                            ->helperText('Original price (for showing discounts)'),
                                    ]),
                            ]),

                        // Tab 3: SEO
                        Forms\Components\Tabs\Tab::make('SEO')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->maxLength(60)
                                    ->helperText('SEO title (max 60 characters)'),
                                Forms\Components\Textarea::make('meta_description')
                                    ->maxLength(160)
                                    ->rows(3)
                                    ->helperText('SEO description (max 160 characters)'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->size(60),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Course $record): string => $record->subtitle ?? ''),
                Tables\Columns\TextColumn::make('school_year')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('topic')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('MYR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->icon('heroicon-m-star')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('school_year')
                    ->options([
                        'Form 1' => 'Form 1',
                        'Form 2' => 'Form 2',
                        'Form 3' => 'Form 3',
                        'Form 4' => 'Form 4',
                        'Form 5' => 'Form 5',
                        'Form 6' => 'Form 6',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
