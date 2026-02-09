<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopicResource\Pages;
use App\Filament\Resources\TopicResource\RelationManagers;
use App\Models\Topic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'VideoLite';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    // Hide from navigation since topics are mainly managed via Subject
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                            ),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from name, but can be customized'),
                        Forms\Components\Select::make('school_year')
                            ->required()
                            ->options([
                                'Form 1' => 'Form 1',
                                'Form 2' => 'Form 2',
                                'Form 3' => 'Form 3',
                                'Form 4' => 'Form 4',
                                'Form 5' => 'Form 5',
                                'Form 6' => 'Form 6',
                            ])
                            ->native(false)
                            ->helperText('School year/grade for this topic'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Appearance')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->image()
                            ->directory('topic-thumbnails')
                            ->maxSize(2048)
                            ->helperText('Recommended size: 800x600px'),
                        Forms\Components\TextInput::make('level')
                            ->maxLength(50)
                            ->placeholder('Beginner, Intermediate, Advanced')
                            ->helperText('Difficulty level (optional)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('draft')
                            ->native(false),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publication Date')
                            ->helperText('When this topic was/will be published'),
                        Forms\Components\TextInput::make('order_column')
                            ->label('Display Order')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-topic.png')),
                Tables\Columns\TextColumn::make('subject.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('school_year')
                    ->colors([
                        'primary' => 'Form 1',
                        'success' => 'Form 2',
                        'warning' => 'Form 3',
                        'danger' => 'Form 4',
                        'info' => 'Form 5',
                        'secondary' => 'Form 6',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->material_count . ' materials'),
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'published',
                        'danger' => 'archived',
                    ]),
                Tables\Columns\TextColumn::make('order_column')
                    ->label('Order')
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('subject')
                    ->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('school_year')
                    ->options([
                        'Form 1' => 'Form 1',
                        'Form 2' => 'Form 2',
                        'Form 3' => 'Form 3',
                        'Form 4' => 'Form 4',
                        'Form 5' => 'Form 5',
                        'Form 6' => 'Form 6',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order_column')
            ->defaultSort('order_column');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopics::route('/'),
            'create' => Pages\CreateTopic::route('/create'),
            'edit' => Pages\EditTopic::route('/{record}/edit'),
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
