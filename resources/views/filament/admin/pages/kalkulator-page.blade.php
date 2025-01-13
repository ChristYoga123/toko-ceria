<x-filament-panels::page>
    <form wire:submit="checkout">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                {{ $this->table }}
            </div>

            <div
                class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
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
                                <button type="button" wire:click="removeFromCart({{ $product->id }})"
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

                    <!-- Kembalian (hanya muncul jika metode pembayaran lunas) -->
                    @if ($data['payment_method'] === 'lunas')
                        <div
                            class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="font-semibold text-gray-950 dark:text-white">Kembalian:</span>
                            <span class="font-bold text-lg text-gray-950 dark:text-white">
                                Rp {{ number_format($this->kembalian, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    <!-- Form Fields -->
                    <div class="space-y-2">
                        {{ $this->form }}
                    </div>

                    <x-filament::button type="submit" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove>Checkout</span>
                        <span wire:loading>Processing...</span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    </form>
</x-filament-panels::page>
