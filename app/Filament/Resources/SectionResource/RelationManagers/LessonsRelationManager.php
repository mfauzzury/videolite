<?php

namespace App\Filament\Resources\SectionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Introduction to Quadratic Equations')
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('Lesson description')
                            ->columnSpan(2),
                        Forms\Components\Select::make('type')
                            ->options([
                                'video' => 'Video',
                                'article' => 'Article',
                            ])
                            ->default('video')
                            ->required(),
                        Forms\Components\TextInput::make('order_column')
                            ->label('Order')
                            ->numeric()
                            ->default(fn () => static::getRelationship()->count() + 1)
                            ->helperText('Display order within section'),
                    ]),

                Forms\Components\Section::make('Video Upload')
                    ->description('Upload the lesson video (MP4 recommended, max 512MB)')
                    ->schema([
                        Forms\Components\FileUpload::make('video')
                            ->disk('videos')
                            ->directory('/')
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/x-msvideo'])
                            ->maxSize(512 * 1024)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && $state instanceof \Illuminate\Http\UploadedFile) {
                                    // Store file path
                                    $filename = $state->getClientOriginalName();
                                    $set('video_filename', $filename);
                                    $set('video_size_bytes', $state->getSize());

                                    // Extract video metadata using getID3
                                    try {
                                        $getID3 = new \getID3;
                                        $fileInfo = $getID3->analyze($state->getRealPath());

                                        if (isset($fileInfo['playtime_seconds'])) {
                                            $set('duration_seconds', (int) $fileInfo['playtime_seconds']);
                                        }
                                    } catch (\Exception $e) {
                                        // If metadata extraction fails, continue without it
                                    }
                                }
                            })
                            ->dehydrated(false),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('duration_seconds')
                                    ->label('Duration (seconds)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-extracted from video'),
                                Forms\Components\TextInput::make('video_size_bytes')
                                    ->label('File Size (bytes)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-extracted'),
                                Forms\Components\TextInput::make('video_filename')
                                    ->label('Filename')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Original filename'),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('PDF Reference Material')
                    ->description('Upload optional PDF reference material for students (max 50MB)')
                    ->schema([
                        Forms\Components\FileUpload::make('pdf')
                            ->disk('lesson-pdfs')
                            ->directory('/')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(50 * 1024)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state && $state instanceof \Illuminate\Http\UploadedFile) {
                                    $set('pdf_filename', $state->getClientOriginalName());
                                }
                            })
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('pdf_filename')
                            ->label('PDF Filename')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Original PDF filename'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('order_column')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->description),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video' => 'success',
                        'article' => 'info',
                    }),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => $state ? gmdate('H:i:s', $state) : 'N/A')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_video')
                    ->label('Video')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => !empty($record->video_path)),
                Tables\Columns\IconColumn::make('has_pdf')
                    ->label('PDF')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => !empty($record->pdf_path)),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'video' => 'Video',
                        'article' => 'Article',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Handle video file upload
                        if (request()->hasFile('video')) {
                            $video = request()->file('video');
                            $path = $video->store('/', 'videos');
                            $data['video_path'] = $path;
                        }

                        // Handle PDF file upload
                        if (request()->hasFile('pdf')) {
                            $pdf = request()->file('pdf');
                            $path = $pdf->store('/', 'lesson-pdfs');
                            $data['pdf_path'] = $path;
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Handle video file upload on edit
                        if (request()->hasFile('video')) {
                            $video = request()->file('video');
                            $path = $video->store('/', 'videos');
                            $data['video_path'] = $path;
                        }

                        // Handle PDF file upload on edit
                        if (request()->hasFile('pdf')) {
                            $pdf = request()->file('pdf');
                            $path = $pdf->store('/', 'lesson-pdfs');
                            $data['pdf_path'] = $path;
                        }

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order_column')
            ->defaultSort('order_column', 'asc');
    }
}
