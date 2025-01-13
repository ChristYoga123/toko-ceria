<?php
namespace App\Filament\Admin\Pages;

use App\Models\Hutang;
use App\Models\Produk;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Transaksi;
use Filament\Tables\Table;
use App\Models\HutangDetail;
use App\Models\TransaksiDetail;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Concerns\InteractsWithActions;

class KalkulatorPage extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable, InteractsWithActions, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'POS';
    protected static string $view = 'filament.admin.pages.kalkulator-page';

    public array $data = [
        'payment_method' => 'lunas',
        'nama_pembeli' => null,
        'jumlah_bayar' => 0,
    ];
    public $cart = [];
    public $quantities = [];
    public $jumlah_bayar = 0;

    public function mount()
    {
        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return 'POS';
    }

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

    #[Computed]
    public function kembalian()
    {
        if ($this->data['payment_method'] === 'lunas') {
            $jumlahBayar = (int)($this->data['jumlah_bayar'] ?? 0);
            return max($jumlahBayar - $this->total, 0);
        }
        return 0;
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
                    ->live()
                    ->afterStateUpdated(function($state, $set) {
                        $set('jumlah_bayar', 0);
                        if ($state === 'lunas') {
                            $set('nama_pembeli', null);
                        }
                    }),

                Select::make('nama_pembeli')
                    ->label('Nama Pembeli')
                    ->placeholder('Pilih nama pembeli')
                    ->visible(fn () => $this->data['payment_method'] === 'hutang')
                    ->required(fn () => $this->data['payment_method'] === 'hutang')
                    ->options(Hutang::pluck('nama_pembeli', 'id'))
                    ->createOptionForm([
                        TextInput::make('nama_pembeli')
                            ->label('Nama Pembeli')
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan nama pembeli')
                            ->required(),
                        TextInput::make('nomor_telepon')
                            ->label('Nomor Telepon')
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan nomor telepon')
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        Hutang::create([
                            'nama_pembeli' => $data['nama_pembeli'],
                            'nomor_telepon' => $data['nomor_telepon'],
                        ]);
                    })
                    ->required()
                    ->live(),

                TextInput::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp')
                    ->suffix(',00')
                    ->required()
                    ->live()
                    ->minValue(0),
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

    public function resetForm()
    {
        // Reset cart and form
        $this->reset(['cart', 'quantities']);
        $this->data['jumlah_bayar'] = 0;
        $this->data['nama_pembeli'] = null;
        $this->data['payment_method'] = 'lunas';
    }

    public function checkout()
    {
        // Validate cart is not empty
        if (empty($this->cart)) {
            Notification::make()
                ->title('Gagal')
                ->body('Keranjang belanja kosong!')
                ->danger()
                ->send();
            return;
        }

        // Validate cart is not empty
        if (empty($this->cart)) {
            Notification::make()
                ->title('Gagal')
                ->body('Keranjang belanja kosong!')
                ->danger()
                ->send();
            return;
        }

        // Validasi payment amount sebelum memulai transaction
        $total = $this->total();
        $jumlahBayar = (int) $this->data['jumlah_bayar'];

        // Untuk metode hutang
        if ($this->data['payment_method'] === 'hutang') {
            if ($jumlahBayar >= $total) {
                Notification::make()
                    ->title('Gagal')
                    ->body('Untuk pembayaran lunas, silahkan pilih metode pembayaran Lunas!')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Untuk metode lunas
        if ($this->data['payment_method'] === 'lunas') {
            if ($jumlahBayar < $total) {
                Notification::make()
                    ->title('Gagal')
                    ->body('Jumlah bayar kurang dari total belanja!')
                    ->danger()
                    ->send();
                return;
            }
        }

        DB::beginTransaction();
        try {
            // Revalidate stock availability before checkout
            foreach ($this->cart as $product) {
                $quantity = $this->quantities[$product->id] ?? 1;
                
                // Refresh product data from database
                $freshProduct = Produk::find($product->id);
                
                if (!$freshProduct || $freshProduct->stok < $quantity) {
                    DB::rollBack();
                    Notification::make()
                        ->title('Gagal')
                        ->body("Stok produk {$product->nama_produk} tidak mencukupi!")
                        ->danger()
                        ->send();
                    return;
                }
            }

            // Create transaction
            $transaction = Transaksi::create([
                'transaksi_id' => 'TRX' . now()->timestamp . rand(1000, 9999),
                'total_harga' => $this->total(),
                'total_bayar' => $this->data['jumlah_bayar'],
                'lunas' => $this->data['payment_method'] === 'lunas',
            ]);

            // Create transaction details
            foreach ($this->cart as $product) {
                $quantity = $this->quantities[$product->id] ?? 1;
                
                TransaksiDetail::create([
                    'transaksi_id' => $transaction->id,
                    'produk_id' => $product->id,
                    'jumlah' => $quantity,
                    'harga_satuan' => $product->harga_jual,
                    'subtotal' => $product->harga_jual * $quantity,
                ]);

                // Update stock
                $product->decrement('stok', $quantity);
            }

            // Handle credit transaction (hutang)
            if ($this->data['payment_method'] === 'hutang') {
                if (empty($this->data['nama_pembeli'])) {
                    throw new \Exception('Nama pembeli harus diisi untuk transaksi hutang!');
                }

                HutangDetail::create([
                    'hutang_id' => $this->data['nama_pembeli'], // Using the selected hutang ID
                    'transaksi_id' => $transaction->id,
                    'jumlah_bayar' => $this->data['jumlah_bayar'],
                    'sisa_hutang' => $this->total() - $this->data['jumlah_bayar'],
                    'lunas' => $this->total() === $this->data['jumlah_bayar'],
                    'tanggal_hutang' => now(),
                ]);
            }

            DB::commit();

            $this->resetForm();

            Notification::make()
                ->title('Berhasil')
                ->body('Transaksi berhasil disimpan!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Gagal')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}