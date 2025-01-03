<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Produk;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\ProdukResource\Pages;
use App\Filament\Admin\Resources\ProdukResource\RelationManagers;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('nama_produk')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('harga_beli')
                            ->required()
                            ->prefix('Rp')
                            ->suffix(',00')
                            ->numeric(),
                        Forms\Components\TextInput::make('harga_jual')
                            ->required()
                            ->prefix('Rp')
                            ->suffix(',00')
                            ->numeric(),
                        Forms\Components\TextInput::make('stok')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('stok_minimal')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('keterangan')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_produk')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('harga_jual')
                    ->numeric()
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stok')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ketersediaan')
                    ->badge()
                    ->getStateUsing(function(Produk $produk)
                    {
                        // jika produk stok lebih dari stok minimal
                        if ($produk->stok > $produk->stok_minimal) {
                            return 'Tersedia';
                        }
                        elseif($produk->stok === 0) {
                            return 'Habis';
                        }
                        elseif($produk->stok <= $produk->stok_minimal) {
                            return 'Hampir Habis';
                        }
                    })
                    ->color(function($state)
                    {
                        if ($state === 'Tersedia') {
                            return 'success';
                        }
                        elseif($state === 'Habis') {
                            return 'danger';
                        }
                        elseif($state === 'Hampir Habis') {
                            return 'warning';
                        }
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('ketersediaan')
                    ->form([
                        Select::make('available')
                            ->label('Ketersediaan')
                            ->options([
                                'tersedia' => 'Tersedia',
                                'habis' => 'Habis',
                                'hampir_habis' => 'Hampir Habis',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['available'] === 'tersedia') {
                            $query->whereColumn('stok', '>', 'stok_minimal');
                        }
                        elseif($data['available'] === 'hampir_habis') {
                            $query->whereColumn('stok', '<=', 'stok_minimal')->where('stok', '>', 0);
                        }
                        elseif($data['available'] === 'habis') {
                            $query->where('stok', 0);
                        }

                        return $query;
                    }),
                ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProduks::route('/'),
        ];
    }
}
