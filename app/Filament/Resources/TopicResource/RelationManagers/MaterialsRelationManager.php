<?php

namespace App\Filament\Resources\TopicResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Material Type')
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->required()
                            ->options([
                                'video' => 'Video',
                                'pdf' => 'PDF Document',
                            ])
                            ->default('video')
                            ->live()
                            ->inline()
                            ->helperText('Select the type of learning material'),
                    ]),

                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
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
                            ->helperText('Auto-generated from title, but can be customized'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Video-specific fields
                Forms\Components\Section::make('Video Content')
                    ->schema([
                        Forms\Components\FileUpload::make('video_path')
                            ->label('Video File')
                            ->required(fn (Forms\Get $get) => $get('type') === 'video')
                            ->disk('videos')
                            ->directory('/')
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo'])
                            ->maxSize(512 * 1024) // 512MB
                            ->helperText('Accepted formats: MP4, MOV, AVI. Max size: 512MB')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;

                                // Store filename and size
                                $set('video_filename', $state->getClientOriginalName());
                                $set('video_size_bytes', $state->getSize());

                                // TODO: Extract duration using getID3 library
                                // For now, you'll need to manually set duration
                            }),
                        Forms\Components\FileUpload::make('thumbnail_path')
                            ->label('Video Thumbnail')
                            ->image()
                            ->directory('material-thumbnails')
                            ->maxSize(2048)
                            ->helperText('Thumbnail image for the video'),
                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Duration (seconds)')
                            ->numeric()
                            ->helperText('Video duration in seconds (e.g., 300 for 5 minutes)'),
                    ])
                    ->columns(3)
                    ->visible(fn (Forms\Get $get) => $get('type') === 'video'),

                // PDF-specific fields
                Forms\Components\Section::make('PDF Content')
                    ->schema([
                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('PDF File')
                            ->required(fn (Forms\Get $get) => $get('type') === 'pdf')
                            ->disk('lesson-pdfs')
                            ->directory('/')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(50 * 1024) // 50MB
                            ->helperText('Max size: 50MB')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;

                                // Store filename and size
                                $set('pdf_filename', $state->getClientOriginalName());
                                $set('pdf_size_bytes', $state->getSize());

                                // TODO: Extract page count using PDF parser
                                // For now, you'll need to manually set page count
                            }),
                        Forms\Components\TextInput::make('pdf_pages')
                            ->label('Number of Pages')
                            ->numeric()
                            ->helperText('Total number of pages in the PDF'),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('type') === 'pdf'),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_preview')
                            ->label('Free Preview')
                            ->helperText('Allow non-enrolled users to access this material')
                            ->inline(false),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->default('draft')
                            ->native(false),
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
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'video',
                        'success' => 'pdf',
                    ])
                    ->icons([
                        'heroicon-o-film' => 'video',
                        'heroicon-o-document-text' => 'pdf',
                    ]),
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-material.png'))
                    ->visible(fn ($record) => $record->type === 'video'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string =>
                        $record->isVideo()
                            ? $record->getFormattedDuration()
                            : ($record->pdf_pages ? $record->pdf_pages . ' pages' : 'PDF')
                    ),
                Tables\Columns\IconColumn::make('is_preview')
                    ->label('Preview')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-open')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'published',
                    ]),
                Tables\Columns\TextColumn::make('order_column')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'video' => 'Videos',
                        'pdf' => 'PDFs',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
                Tables\Filters\TernaryFilter::make('is_preview')
                    ->label('Free Preview')
                    ->placeholder('All materials')
                    ->trueLabel('Preview only')
                    ->falseLabel('Locked only'),
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
            ->defaultSort('order_column')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
