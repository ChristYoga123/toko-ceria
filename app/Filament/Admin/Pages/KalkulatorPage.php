<?php
namespace App\Filament\Admin\Pages;

use App\Models\Produk;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Livewire\Attributes\Computed;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Concerns\InteractsWithActions;

class KalkulatorPage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static string $view = 'filament.admin.pages.kalkulator-page';

    public $cart = [];
    public $quantities = [];
    public $jumlah_bayar = 0;
    // Add these properties to your KalkulatorPage class:
    public $payment_method = 'lunas';
    public $customer_name = '';
    public $customer_phone = '';

    public function addToCart(Produk $produk)
    {
        // Jika produk sudah ada di cart, tambah quantity
        if (isset($this->cart[$produk->id])) {
            $this->quantities[$produk->id]++;
        } else {
            // Jika produk belum ada, tambahkan ke cart dengan quantity 1
            $this->cart[$produk->id] = $produk;
            $this->quantities[$produk->id] = 1;
        }

        // Pastikan quantity tidak melebihi stok
        if ($this->quantities[$produk->id] > $produk->stok) {
            $this->quantities[$produk->id] = $produk->stok;
            Notification::make()
                ->title('Gagal')
                ->body('Jumlah melebihi stok yang tersedia!')
                ->danger()
                ->send();
            
            return;
        }
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
        unset($this->quantities[$productId]);
    }

    public function updateQuantity($productId, $quantity)
    {
        $produk = $this->cart[$productId];
        
        // Validasi quantity
        if ($quantity <= 0) {
            $this->quantities[$productId] = 1;
            return;
        }
        
        // Validasi stok
        if ($quantity > $produk->stok) {
            $this->quantities[$productId] = $produk->stok;
            Notification::make()
                ->title('Gagal')
                ->body('Jumlah melebihi stok yang tersedia!')
                ->danger()
                ->send();
            return;
        }

        $this->quantities[$productId] = $quantity;
    }

    #[Computed]
    public function total()
    {
        $total = 0;
        foreach ($this->cart as $product) {
            $quantity = isset($this->quantities[$product->id]) ? (int)$this->quantities[$product->id] : 1;
            $total += $product->harga_jual * $quantity;
        }
        return $total;
    }

    // Update the checkout method:
    public function checkout()
    {
        // Validasi stok sebelum checkout
        foreach ($this->cart as $product) {
            if ($this->quantities[$product->id] > $product->stok) {
                Notification::make()
                    ->title('Gagal')
                    ->body('Jumlah melebihi stok yang tersedia!')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Validasi pembayaran
        if ($this->payment_method === 'lunas' && $this->jumlah_bayar < $this->total) {
            Notification::make()
                ->title('Gagal')
                ->body('Jumlah bayar kurang!')
                ->danger()
                ->send();
            return;
        }

        // Validasi data pelanggan untuk hutang
        if ($this->payment_method === 'hutang') {
            if (empty($this->customer_name) || empty($this->customer_phone)) {
                Notification::make()
                    ->title('Gagal')
                    ->body('Data pelanggan harus diisi untuk pembayaran hutang!')
                    ->danger()
                    ->send();
                return;
            }

            if ($this->jumlah_bayar <= 0) {
                Notification::make()
                    ->title('Gagal')
                    ->body('Jumlah cicilan harus lebih dari 0!')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Kurangi stok
        foreach ($this->cart as $product) {
            $product->stok -= $this->quantities[$product->id];
            $product->save();
        }

        // Reset cart and form
        $this->cart = [];
        $this->quantities = [];
        $this->jumlah_bayar = 0;
        $this->payment_method = 'lunas';
        $this->customer_name = '';
        $this->customer_phone = '';

        Notification::make()
            ->title('Berhasil')
            ->body('Checkout berhasil!')
            ->success()
            ->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'lunas' => 'Lunas',
                        'hutang' => 'Hutang'
                    ])
                    ->default('lunas')
                    ->inline()
                    ->live(),
                    
                Section::make()
                    ->schema([
                        TextInput::make('jumlah_bayar')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->live()
                            ->default(0)
                            ->required(),
                            
                        TextInput::make('kembalian')
                            ->label('Kembalian')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn () => 'Rp ' . number_format((int) ($this->jumlah_bayar ?? 0) - $this->total, 0, ',', '.'))
                    ])
                    ->visible(fn (callable $get) => $get('payment_method') === 'lunas'),

                Section::make()
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->required(),
                        TextInput::make('customer_phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->required(),
                        TextInput::make('jumlah_bayar')
                            ->label('Jumlah Cicilan')
                            ->numeric()
                            ->live()
                            ->default(0)
                            ->required(),
                        TextInput::make('sisa_hutang')
                            ->label('Sisa Hutang')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn () => 'Rp ' . number_format($this->total - ($this->jumlah_bayar ?? 0), 0, ',', '.'))
                    ])
                    ->visible(fn (callable $get) => $get('payment_method') === 'hutang'),
            ])
            ->statePath('data');

    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Produk::query())
            ->columns([
                TextColumn::make('nama_produk')
                    ->searchable(),
                TextColumn::make('harga_jual')
                    ->searchable()
                    ->money('IDR'),
                TextColumn::make('stok')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ketersediaan')
                    ->badge()
                    ->getStateUsing(function(Produk $produk) {
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
                    ->color(function($state) {
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
            ])
            ->actions([
                Action::make('addToCart')
                    ->label('')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('danger')
                    ->iconSize('lg')
                    ->action(fn (Produk $record) => $this->addToCart($record))
                    ->visible(fn (Produk $record) => $record->stok > 0) // Sembunyikan tombol jika stok habis
            ]);
    }
}