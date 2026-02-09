<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'VideoLite';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Type')
                    ->description('Choose what this package includes')
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->required()
                            ->options([
                                'subject' => 'Entire Subject - Access to all topics in a subject',
                                'subject_year' => 'Subject by Year - Access to all topics in a specific form/year',
                                'topic' => 'Individual Topic - Access to a single topic only',
                            ])
                            ->descriptions([
                                'subject' => 'User gets all current and future topics in this subject',
                                'subject_year' => 'User gets all topics for a specific form (e.g., Form 1)',
                                'topic' => 'User gets access to one specific topic only',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Clear fields when type changes
                                if ($state === 'subject') {
                                    $set('school_year', null);
                                    $set('topic_id', null);
                                } elseif ($state === 'subject_year') {
                                    $set('topic_id', null);
                                } elseif ($state === 'topic') {
                                    $set('subject_id', null);
                                    $set('school_year', null);
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Package Scope')
                    ->schema([
                        // Show for 'subject' and 'subject_year'
                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->required(fn (Forms\Get $get) => in_array($get('type'), ['subject', 'subject_year']))
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['subject', 'subject_year']))
                            ->searchable()
                            ->preload()
                            ->live(),

                        // Show only for 'subject_year'
                        Forms\Components\Select::make('school_year')
                            ->label('School Year')
                            ->required(fn (Forms\Get $get) => $get('type') === 'subject_year')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'subject_year')
                            ->options([
                                'Form 1' => 'Form 1',
                                'Form 2' => 'Form 2',
                                'Form 3' => 'Form 3',
                                'Form 4' => 'Form 4',
                                'Form 5' => 'Form 5',
                                'Form 6' => 'Form 6',
                            ])
                            ->native(false),

                        // Show only for 'topic'
                        Forms\Components\Select::make('topic_id')
                            ->label('Topic')
                            ->relationship('topic', 'name')
                            ->required(fn (Forms\Get $get) => $get('type') === 'topic')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'topic')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->subject->name} - {$record->school_year} - {$record->name}"),
                    ])
                    ->columns(2),

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
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from name, but can be customized'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Describe what is included in this package'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Price (RM)')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('RM')
                            ->minValue(0)
                            ->step('0.01'),
                        Forms\Components\TextInput::make('compare_at_price')
                            ->label('Compare at Price (RM)')
                            ->numeric()
                            ->prefix('RM')
                            ->minValue(0)
                            ->step('0.01')
                            ->helperText('Original price before discount (optional)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Display')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail_path')
                            ->label('Thumbnail')
                            ->image()
                            ->directory('package-thumbnails')
                            ->maxSize(2048)
                            ->helperText('Recommended size: 800x600px'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Package')
                            ->helperText('Show on homepage or featured section')
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->native(false),
                        Forms\Components\TextInput::make('order_column')
                            ->label('Display Order')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->maxLength(60)
                            ->helperText('Maximum 60 characters'),
                        Forms\Components\TextInput::make('meta_description')
                            ->maxLength(160)
                            ->helperText('Maximum 160 characters'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-package.png')),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'subject',
                        'success' => 'subject_year',
                        'warning' => 'topic',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'subject' => 'Subject',
                        'subject_year' => 'Subject + Year',
                        'topic' => 'Topic',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record): string {
                        $topics = $record->getIncludedTopics();
                        $count = $topics->count();
                        $materials = $topics->sum('material_count');
                        return "{$count} topics • {$materials} materials";
                    }),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Includes')
                    ->getStateUsing(function ($record): string {
                        if ($record->isSubjectPackage()) {
                            return $record->subject->name . ' (All Years)';
                        } elseif ($record->isSubjectYearPackage()) {
                            return $record->subject->name . ' - ' . $record->school_year;
                        } elseif ($record->isTopicPackage()) {
                            return $record->topic->name;
                        }
                        return '-';
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->money('MYR')
                    ->sortable()
                    ->description(fn ($record): ?string =>
                        $record->hasDiscount()
                            ? 'RM ' . number_format($record->compare_at_price, 2) . ' (-' . $record->getDiscountPercentage() . '%)'
                            : null
                    ),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
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
                        'subject' => 'Entire Subject',
                        'subject_year' => 'Subject by Year',
                        'topic' => 'Individual Topic',
                    ]),
                Tables\Filters\SelectFilter::make('subject')
                    ->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
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
