<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            {{ $this->table }}
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Keranjang Belanja</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($cart) }} item</span>
            </div>

            <div class="space-y-4 max-h-[400px] overflow-y-auto p-4">
                @foreach ($cart as $product)
                    <div class="flex justify-between items-center p-2 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-950 dark:text-white">{{ $product->nama_produk }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Rp {{ number_format($product->harga_jual, 0, ',', '.') }}
                            </p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <input type="number" wire:model.live="quantities.{{ $product->id }}"
                                wire:change="updateQuantity({{ $product->id }}, $event.target.value)"
                                class="fi-input block w-16 h-8 rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-500"
                                min="1">
                            <button wire:click="removeFromCart({{ $product->id }})"
                                class="text-danger-600 hover:text-danger-500 dark:text-danger-500 dark:hover:text-danger-400">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                @endforeach

                @if (empty($cart))
                    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                        Keranjang masih kosong
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-200 dark:border-gray-800 p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-gray-950 dark:text-white">Total:</span>
                    <span class="font-bold text-lg text-gray-950 dark:text-white">
                        Rp {{ number_format($this->total, 0, ',', '.') }}
                    </span>
                </div>

                <!-- Opsi Pembayaran -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        Metode Pembayaran
                    </label>
                    <div class="flex space-x-4 gap-2">
                        <label class="flex items-center space-x-2">
                            <input type="radio" wire:model.live="payment_method" value="lunas"
                                class="filament-forms-radio-input dark:checked:bg-primary-500">
                            <span class="text-sm text-black dark:text-white">
                                Lunas
                            </span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="radio" wire:model.live="payment_method" value="hutang"
                                class="filament-forms-radio-input dark:checked:bg-primary-500">
                            <span class="text-sm text-black dark:text-white">
                                Hutang
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Form Pembayaran -->
                <div class="space-y-3">
                    @if ($payment_method === 'hutang')
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Nama Pelanggan</label>
                            <input type="text" wire:model="customer_name"
                                class="mt-1 fi-input block w-full h-8 rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-500">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">No. Telepon</label>
                            <input type="text" wire:model="customer_phone"
                                class="mt-1 fi-input block w-full h-8 rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-500">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Jumlah Cicilan</label>
                            <input type="number" wire:model.live="jumlah_bayar"
                                class="mt-1 fi-input block w-full h-8 rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-500"
                                min="0">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Sisa Hutang</label>
                            <input type="text" readonly
                                value="Rp {{ number_format($this->total - ($jumlah_bayar ?? 0), 0, ',', '.') }}"
                                class="mt-1 fi-input block w-full h-8 rounded-lg border-gray-300 bg-gray-50 shadow-sm outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        </div>
                    @else
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Jumlah Bayar</label>
                            <input type="number" wire:model.live="jumlah_bayar"
                                class="mt-1 fi-input block w-full h-8 rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-500"
                                min="0">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Kembalian</label>
                            <input type="text" readonly
                                value="Rp {{ number_format((int) ($jumlah_bayar ?? 0) - $this->total, 0, ',', '.') }}"
                                class="mt-1 fi-input block w-full h-8 rounded-lg border-gray-300 bg-gray-50 shadow-sm outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        </div>
                    @endif
                </div>

                <button wire:click="checkout" @class([
                    'w-full fi-btn fi-btn-size-md relative grid grid-flow-col items-center justify-center gap-x-2 rounded-lg font-semibold outline-none transition duration-75 focus:ring-2',
                    'bg-primary-600 text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400' => !empty(
                        $cart
                    ),
                    'bg-gray-300 text-gray-500 dark:bg-gray-600 dark:text-gray-400 cursor-not-allowed' => empty(
                        $cart
                    ),
                ]) @disabled(empty($cart))>
                    Checkout
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
