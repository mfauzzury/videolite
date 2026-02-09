<?php

namespace App\Filament\Resources\SubjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-topic.png')),
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
            ])
            ->filters([
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            ->defaultSort('school_year')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
