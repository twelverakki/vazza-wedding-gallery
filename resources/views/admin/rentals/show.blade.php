@extends('layouts.admin')

@php
    $statusColor = match ($rental->status) {
        'waiting' => 'yellow',
        'booked' => 'gray',
        'rented' => 'blue',
        'returned' => 'orange',
        'completed' => 'green',
        'canceled' => 'red',
        default => 'gray'
    };
    $statusLabel = match ($rental->status) {
        'waiting' => 'Menunggu Konfirmasi',
        'booked' => 'Booked',
        'rented' => 'Disewa',
        'returned' => 'Dikembalikan',
        'completed' => 'Selesai',
        'canceled' => 'Batal',
        default => ucfirst($rental->status)
    };
@endphp

@section('header')
    <div x-data="{ showCustomer: false }" class="sticky top-0 z-30 shadow-sm">
        <!-- Main Header Bar -->
        <div class="flex items-center justify-between px-4 py-3">
            <!-- Left: Back Button (Icon Only) -->
            <a href="{{ route('rentals.index') }}" class="text-gray-950 hover:text-gray-900 p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>

            <!-- Center: Customer Name & Toggle -->
            <button @click="showCustomer = !showCustomer" class="flex items-center gap-2 focus:outline-none group">
                <h1 class="text-lg font-bold text-gray-950 transition-colors">
                    {{ $rental->customer->name }}
                </h1>
                <svg class="w-4 h-4 text-gray-950 transition-transform duration-200" :class="{'rotate-180': showCustomer}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <div class="w-2 h-2 rounded-full bg-{{ $statusColor }}-500 animate-pulse"></div>
            </button>

            <!-- Edit Button -->
            <a href="{{ route('rentals.edit', $rental->id) }}" class="text-gray-950 p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
            </a>
        </div>

        <!-- Collapsible Customer Data -->
        <div x-show="showCustomer" style="display: none;" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" class="px-4 pb-4">

            <!-- Responsive Grid -->
            <div class="pt-4 space-y-3 text-sm text-gray-600 sm:space-y-0 sm:grid sm:grid-cols-2 md:grid-cols-4 sm:gap-4">

                <!-- Card 1: Kode Transaksi -->
                <div
                    class="flex justify-between items-center border-b border-gray-200 pb-2 sm:border-0 sm:bg-white sm:p-4 sm:rounded-xl sm:shadow-sm sm:flex-col sm:items-start sm:gap-1">
                    <span class="sm:text-xs sm:uppercase sm:font-bold sm:tracking-wider">Kode Transaksi</span>
                    <span class="font-mono font-bold text-gray-800 sm:text-lg">{{ $rental->code }}</span>
                </div>

                <!-- Card 2: Nomor HP -->
                <div
                    class="flex justify-between items-center border-b border-gray-200 pb-2 sm:border-0 sm:bg-white sm:p-4 sm:rounded-xl sm:shadow-sm sm:flex-col sm:items-start sm:gap-1">
                    <span class="sm:text-xs sm:uppercase sm:font-bold sm:tracking-wider">Nomor HP</span>
                    <span class="font-bold text-gray-800 sm:text-base">{{ $rental->customer->phone }}</span>
                </div>

                <!-- Card 3: Alamat -->
                <div
                    class="flex justify-between items-start border-b border-gray-200 pb-2 sm:border-0 sm:bg-white sm:p-4 sm:rounded-xl sm:shadow-sm sm:flex-col sm:items-start sm:gap-1 sm:col-span-2 md:col-span-1">
                    <span
                        class="whitespace-nowrap mr-4 sm:mr-0 sm:text-xs sm:uppercase sm:font-bold sm:tracking-wider">Alamat</span>
                    <span class="font-bold text-gray-800 text-right sm:text-left sm:text-sm sm:leading-snug sm:line-clamp-2"
                        title="{{ $rental->customer->address }}">{{ $rental->customer->address ?? '-' }}</span>
                </div>

                <!-- Card 4: Status -->
                <div
                    class="flex justify-between items-center pt-1 sm:bg-white sm:p-4 sm:rounded-xl sm:shadow-sm sm:flex-col sm:items-start sm:gap-2">
                    <span class="sm:text-xs sm:uppercase sm:font-bold sm:tracking-wider">Status Rental</span>

                    <span
                        class="px-2 py-0.5 rounded text-xs font-bold border border-{{ $statusColor }}-200 bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 sm:px-3 sm:py-1 sm:text-sm">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>

            <!-- Customer Stats -->
            <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3">
                <!-- Total Sewa -->
                <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm text-center">
                    <span class="block text-[10px] text-gray-400 uppercase font-bold tracking-wider">Total Sewa</span>
                    <span class="block text-lg font-bold text-gray-800 mt-1">{{ $customerStats['total_rentals'] }}x</span>
                </div>
                <!-- Total Belanja -->
                <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm text-center">
                    <span class="block text-[10px] text-gray-400 uppercase font-bold tracking-wider">Total Belanja</span>
                    <span class="block text-lg font-bold text-gray-800 mt-1">Rp
                        {{ number_format($customerStats['total_spend'] / 1000, 0) }}k</span>
                </div>
                <!-- Terakhir Sewa -->
                <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm text-center col-span-2 md:col-span-1">
                    <span class="block text-[10px] text-gray-400 uppercase font-bold tracking-wider">Terakhir Sewa</span>
                    <span class="block text-lg font-bold text-gray-800 mt-1">
                        {{ $customerStats['last_rental'] ? $customerStats['last_rental']->format('d M Y') : 'Baru' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="px-4 pb-3 pt-1">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <!-- WA Generator Button -->
                <button onclick="openInvoiceModal('{{ $rental->id }}')"
                    class="w-full bg-green-50 text-green-600 border border-green-200 rounded-xl py-3 flex items-center justify-center gap-2 font-bold text-sm tracking-wide hover:bg-green-100 active:scale-[0.98] transition-all shadow-sm">
                    <svg class="w-5 h-5 transition-transform group-hover:rotate-12" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                    </svg>
                    <span>WA Generator</span>
                </button>

                <!-- Update Status Rental Button -->
                <button onclick="openStatusModal()"
                    class="w-full bg-blue-50 text-blue-600 border border-blue-200 rounded-xl py-3 flex items-center justify-center gap-2 font-bold text-sm tracking-wide hover:bg-blue-100 active:scale-[0.98] transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Update Status</span>
                </button>

                <!-- Preview Invoice PDF Button -->
                <a href="{{ route('rentals.invoice', $rental->id) }}" target="_blank"
                    class="w-full bg-purple-50 text-purple-700 border border-purple-200 rounded-xl py-3 flex items-center justify-center gap-2 font-bold text-sm tracking-wide hover:bg-purple-100 active:scale-[0.98] transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3v6h6M9 13h6M9 17h4">
                        </path>
                    </svg>
                    <span>Invoice PDF</span>
                </a>
            </div>
        </div>

    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            <!-- Kolom Kiri: Informasi & Item -->

            <div
                class="px-4 py-3 border-t border-gray-100 bg-gray-50 rounded-lg flex flex-col lg:flex-row lg:justify-between flex-wrap gap-2">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Invoice</p>
                    <p class="text-xs pl-6 sm:pl-0 font-mono font-bold text-gray-700">
                        {{ $rental->code ?? 'TRX-#' . $rental->id }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Lokasi Kirim</p>
                    <p class="text-xs pl-6 sm:pl-0 font-bold text-gray-700">
                        {{ $rental->shipping_address ?? '-' }}
                    </p>
                </div>
                <div class="sm:text-right">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Tanggal Sewa</p>
                    <p class="text-xs pl-6 sm:pl-0 font-bold text-gray-700">
                        {{ $rental->start_date->format('d/m/Y') }} - {{ $rental->due_date->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <!-- List Barang (Redesigned) -->
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100"
                x-data="{
                                                                                                                                                                                                                        selected: [],
                                                                                                                                                                                                                        allIds: {{ $rental->items->pluck('id') }},
                                                                                                                                                                                                                        get allSelected() {
                                                                                                                                                                                                                            return this.selected.length === this.allIds.length;
                                                                                                                                                                                                                        },
                                                                                                                                                                                                                        toggle(id) {
                                                                                                                                                                                                                            if (this.selected.includes(id)) {
                                                                                                                                                                                                                                this.selected = this.selected.filter(x => x !== id);
                                                                                                                                                                                                                            } else {
                                                                                                                                                                                                                                this.selected.push(id);
                                                                                                                                                                                                                            }
                                                                                                                                                                                                                        },
                                                                                                                                                                                                                        toggleAll() {
                                                                                                                                                                                                                            if (this.allSelected) {
                                                                                                                                                                                                                                this.selected = [];
                                                                                                                                                                                                                            } else {
                                                                                                                                                                                                                                this.selected = [...this.allIds];
                                                                                                                                                                                                                            }
                                                                                                                                                                                                                        }
                                                                                                                                                                                                                    }">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-gray-800 text-base">Item Disewa ({{ $rental->items->count() }})</h3>
                        <button @click="toggleAll()"
                            class="text-xs px-2 py-1 rounded border transition-colors flex items-center gap-1"
                            :class="allSelected ? 'bg-pink-100 text-pink-700 border-pink-200' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'">
                            <span x-text="allSelected ? 'Unselect All' : 'Select All'"></span>
                        </button>
                    </div>

                    <!-- Bulk Action Toolbar (Simplified) -->
                    <div x-show="selected.length > 0" x-transition class="flex items-center gap-2">
                        <span class="text-xs font-bold text-pink-600" x-text="selected.length + ' dipilih'"></span>
                        <form action="{{ route('rentals.bulkUpdateItemMark', $rental->id) }}" method="POST"
                            class="flex gap-1">
                            @csrf
                            <template x-for="id in selected" :key="id">
                                <input type="hidden" name="item_ids[]" :value="id">
                            </template>
                            <select name="mark" onchange="this.form.submit()"
                                class="text-xs font-bold rounded border-gray-200 py-1 pl-2 pr-6 focus:ring-1 focus:ring-pink-200 cursor-pointer h-8">
                                <option value="">Aksi...</option>
                                <option value="normal">Normal</option>
                                <option value="damaged">Rusak</option>
                                <option value="lost">Hilang</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($rental->items as $item)
                        <div @click="toggle({{ $item->id }})"
                            class="relative p-3 rounded-lg border transition-all cursor-pointer group select-none flex items-start gap-3"
                            :class="selected.includes({{ $item->id }}) ? 'border-pink-500 bg-pink-50/30 ring-1 ring-pink-500' : 'border-gray-100 bg-white hover:border-pink-300'">

                            <!-- Image -->
                            <div class="w-10 h-10 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                @if($item->product->image)
                                    <img src="{{ asset('storage/' . $item->product->image) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300 text-xs">IMG</div>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-800 text-xs sm:text-sm truncate pr-6">{{ $item->product->name }}
                                </h4>
                                <p class="text-[10px] text-gray-500">
                                    {{ (float) $item->quantity }} {{ $item->unit }} | Rp
                                    {{ number_format($item->price_at_rental * $item->quantity, 0, ',', '.') }}
                                    @if($item->notes)
                                        <span class="text-pink-500 font-medium italic ml-1">•
                                            {{ \Illuminate\Support\Str::limit($item->notes, 15) }}</span>
                                    @endif
                                </p>
                            </div>

                            <!-- Mark Badge (Absolute Top Right) -->
                            @php
                                $markLabel = match ($item->mark) {
                                    'normal' => 'Normal',
                                    'damaged' => 'Rusak',
                                    'lost' => 'Hilang',
                                    default => ucfirst($item->mark)
                                };
                                $markColor = match ($item->mark) {
                                    'normal' => 'bg-green-100 text-green-700 border-green-200',
                                    'damaged' => 'bg-orange-100 text-orange-700 border-orange-200',
                                    'lost' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200'
                                };
                            @endphp

                            <span
                                class="absolute top-2 right-2 px-1.5 py-0.5 rounded text-[9px] font-bold border {{ $markColor }}">
                                {{ $markLabel }}
                            </span>

                            <!-- Selected Checkmark -->
                            <div x-show="selected.includes({{ $item->id }})"
                                class="absolute -top-1 -right-1 bg-pink-500 text-white rounded-full p-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Raw WA Text (Collapsed) -->
            @if($rental->raw_wa_text)
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="w-full px-4 py-3 flex items-center justify-between bg-gray-50 text-xs font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <span>Raw Data (WhatsApp)</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse>
                        <div
                            class="p-4 mb-4 text-[10px] font-mono text-gray-600 whitespace-pre-wrap max-h-40 overflow-y-auto border-t border-gray-100">
                            {{ $rental->raw_wa_text }}
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Kolom Kanan: Keuangan & Denda -->
        <div class="space-y-6">

            <!-- Summary Keuangan -->
            <div class="bg-gray-900 rounded-lg p-6 text-white relative overflow-hidden shadow-xl">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>

                @php
                    $balance = $rental->remaining_balance;
                    $isNegative = $balance < 0;
                    $isZero = $balance == 0;
                @endphp

                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">
                    @if($isNegative)
                        Kelebihan Bayar
                    @elseif($isZero)
                        Status Pembayaran
                    @else
                        Sisa Pembayaran
                    @endif
                </p>
                <h3
                    class="text-4xl font-bold mb-6 {{ $isNegative ? 'text-orange-400' : ($isZero ? 'text-green-400' : 'text-white') }}">
                    @if($isNegative)
                        Rp {{ number_format(abs($balance), 0, ',', '.') }}
                    @else
                        Rp {{ number_format($balance, 0, ',', '.') }}
                    @endif
                </h3>

                <div class="space-y-3 text-sm border-t border-gray-700 pt-4">
                    <div class="flex justify-between text-gray-400">
                        <span>Subtotal (Gross)</span>
                        <span>Rp {{ number_format($rental->total_gross, 0) }}</span>
                    </div>
                    @if($rental->discount_order > 0)
                        <div class="flex justify-between text-green-400 font-medium">
                            <span>Diskon</span>
                            <span>- Rp {{ number_format($rental->discount_order, 0) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-white font-bold pb-2 border-b border-gray-700">
                        <span>Total Sewa (Net)</span>
                        <span>Rp {{ number_format($rental->total_fee, 0) }}</span>
                    </div>

                    <div class="flex justify-between text-gray-400 pt-2">
                        <span>Ongkir</span>
                        <span>Rp {{ number_format($rental->shipping_cost, 0) }}</span>
                    </div>
                    @if($rental->additional_cost > 0)
                        <div class="flex justify-between text-blue-400 font-medium">
                            <span>Biaya Tambahan</span>
                            <span>+ Rp {{ number_format($rental->additional_cost, 0) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-red-400 font-medium">
                        <span>Total Denda</span>
                        <span>+ Rp {{ number_format($rental->total_fines, 0) }}</span>
                    </div>

                    <div class="my-2 border-t border-gray-700 border-dashed opacity-50"></div>

                    <div class="flex justify-between text-gray-400 text-sm">
                        <span>Info DP Awal</span>
                        <span>Rp {{ number_format($rental->down_payment, 0) }}</span>
                    </div>

                    <div class="flex justify-between text-green-400 font-medium pb-2 border-b border-gray-700 mt-1">
                        <span>Total Sudah Bayar</span>
                        <span>Rp {{ number_format($rental->paid_amount, 0) }}</span>
                    </div>
                    @if($rental->payment_method)
                        <div class="flex justify-between text-gray-500 text-xs italic pt-1">
                            <span>Metode Pembayaran</span>
                            <span>{{ $rental->payment_method }}</span>
                        </div>
                    @endif
                </div>

                @if($rental->status === 'canceled')
                    <!-- Canceled Badge -->
                    <div class="mt-6 bg-red-500/20 text-red-400 text-center py-3 rounded-xl font-bold border border-red-500/30">
                        DIBATALKAN
                    </div>
                @elseif($rental->status === 'waiting')
                    <!-- Waiting Status Info -->
                    <div
                        class="mt-6 bg-yellow-500/20 text-yellow-400 text-center py-3 rounded-xl font-bold border border-yellow-500/30">
                        MENUNGGU KONFIRMASI CUSTOMER
                    </div>
                    <p class="text-xs text-gray-400 text-center mt-2">
                        Ubah status ke "Booked" untuk melakukan pembayaran
                    </p>
                @elseif($rental->remaining_balance > 0 && in_array($rental->status, ['booked', 'rented', 'returned']))
                    <div x-data="{ showPaymentConfirm: false }">
                        <button @click="showPaymentConfirm = true" type="button"
                            class="w-full bg-white text-gray-900 py-3 rounded-lg font-bold hover:bg-gray-50 transition-colors shadow-sm active:scale-95 transform duration-200 mt-4">
                            Lunasi Sekarang
                        </button>

                        <!-- Payment Confirmation Modal -->
                        <div x-show="showPaymentConfirm" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
                            aria-modal="true" style="display: none;">
                            <!-- Backdrop -->
                            <div x-show="showPaymentConfirm" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
                                @click="showPaymentConfirm = false"></div>

                            <div class="flex items-center justify-center min-h-screen p-4">
                                <div x-show="showPaymentConfirm" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 overflow-hidden">

                                    <!-- Modal Content -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Pelunasan</h3>
                                        <p class="text-sm text-gray-500 mb-6">
                                            Anda akan mencatat pelunasan sebesar:
                                            <br>
                                            <span class="text-2xl font-bold text-green-600 block mt-2">
                                                Rp {{ number_format($rental->remaining_balance, 0, ',', '.') }}
                                            </span>
                                        </p>

                                        <div class="grid grid-cols-2 gap-3">
                                            <button @click="showPaymentConfirm = false" type="button"
                                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition-colors">
                                                Batal
                                            </button>
                                            <form action="{{ route('rentals.pay', $rental->id) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-green-200 transition-transform active:scale-95">
                                                    Ya, Lunasi
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($rental->remaining_balance < 0)
                    <div x-data="{ showAdjustConfirm: false }">
                        <button @click="showAdjustConfirm = true" type="button"
                            class="w-full bg-orange-500 text-white py-3 rounded-lg font-bold hover:bg-orange-600 transition-colors shadow-sm active:scale-95 transform duration-200 mt-4">
                            Sesuaikan Saldo
                        </button>

                        <!-- Adjustment Confirmation Modal -->
                        <div x-show="showAdjustConfirm" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
                            aria-modal="true" style="display: none;">
                            <!-- Backdrop -->
                            <div x-show="showAdjustConfirm" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
                                @click="showAdjustConfirm = false"></div>

                            <div class="flex items-center justify-center min-h-screen p-4">
                                <div x-show="showAdjustConfirm" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 overflow-hidden">

                                    <!-- Modal Content -->
                                    <div class="text-center">
                                        <div
                                            class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 mb-6">
                                            <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                </path>
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 mb-3">Penyesuaian Saldo Diperlukan</h3>
                                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4 text-left">
                                            <p class="text-sm text-gray-700 leading-relaxed">
                                                Terdapat perubahan dalam pencatatan transaksi yang telah dilakukan sebelumnya.
                                                Sistem mendeteksi adanya kelebihan pembayaran sebesar:
                                            </p>
                                            <p class="text-2xl font-bold text-orange-600 text-center mt-3">
                                                Rp {{ number_format(abs($rental->remaining_balance), 0, ',', '.') }}
                                            </p>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-6">
                                            Sistem akan menyesuaikan total pembayaran tercatat agar seimbang dengan total
                                            tagihan saat ini.
                                        </p>

                                        <div class="grid grid-cols-2 gap-3">
                                            <button @click="showAdjustConfirm = false" type="button"
                                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition-colors">
                                                Batal
                                            </button>
                                            <form action="{{ route('rentals.pay', $rental->id) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-orange-200 transition-transform active:scale-95">
                                                    Sesuaikan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div
                        class="mt-6 bg-green-500/20 text-green-400 text-center py-3 rounded-xl font-bold border border-green-500/30">
                        LUNAS
                    </div>
                @endif
            </div>

            <!-- Masalah / Denda & Note (Unified) -->
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100 space-y-6">

                <!-- Notes Section -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Catatan Rental</h3>
                        <button onclick="openNoteModal()"
                            class="text-xs text-pink-500 hover:text-pink-700 font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                </path>
                            </svg>
                            Edit
                        </button>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-h-[60px]">
                        @if($rental->notes)
                            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $rental->notes }}</p>
                        @else
                            <p class="text-xs text-gray-400 italic">Tidak ada catatan.</p>
                        @endif
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Biaya Tambahan & Denda List -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Biaya Lainnya</h3>
                        @if($rental->status !== 'canceled')
                            <div x-data="{ expanded: false }" class="relative">
                                <button @click="expanded = !expanded"
                                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded font-bold flex items-center gap-1">
                                    + Tambah
                                </button>
                                <!-- Dropdown Menu -->
                                <div x-show="expanded" @click.away="expanded = false"
                                    class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-10 py-1"
                                    style="display: none;">
                                    <a href="#"
                                        onclick="document.getElementById('form-additional-cost').classList.toggle('hidden'); expanded=false;"
                                        class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">Biaya Tambahan</a>
                                    <a href="#"
                                        onclick="document.getElementById('form-fine').classList.toggle('hidden'); expanded=false;"
                                        class="block px-4 py-2 text-xs text-red-600 hover:bg-red-50">Denda / Kerusakan</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- List Existing -->
                    <div class="space-y-3">
                        @if($rental->additional_cost > 0)
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 flex justify-between items-start">
                                <div>
                                    <p class="text-xs font-bold text-blue-700">Biaya Tambahan</p>
                                    <p class="text-[10px] text-blue-500">{{ $rental->additional_cost_note }}</p>
                                </div>
                                <span class="text-xs font-bold text-blue-700">Rp
                                    {{ number_format($rental->additional_cost, 0) }}</span>
                            </div>
                        @endif

                        @foreach($rental->fines as $fine)
                            <div class="bg-red-50 p-3 rounded-lg border border-red-100 flex justify-between items-start"
                                x-data="{ showDeleteModal: false }">
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-red-700">Denda:
                                        {{ $fine->product ? $fine->product->name : 'Umum' }}
                                    </p>
                                    <p class="text-[10px] text-red-500">{{ $fine->note }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-red-700">Rp {{ number_format($fine->amount, 0) }}</span>
                                    <button type="button" @click="showDeleteModal = true"
                                        class="text-red-400 hover:text-red-600 transition-colors p-1" title="Hapus Denda">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
                                    aria-modal="true" style="display: none;">
                                    <!-- Backdrop -->
                                    <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
                                        @click="showDeleteModal = false"></div>

                                    <div class="flex items-center justify-center min-h-screen p-4">
                                        <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
                                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                            x-transition:leave="ease-in duration-200"
                                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                            class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 overflow-hidden">

                                            <!-- Modal Content -->
                                            <div class="text-center">
                                                <div
                                                    class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                                                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Denda?</h3>
                                                <p class="text-sm text-gray-500 mb-2">
                                                    Denda: <span
                                                        class="font-bold text-gray-700">{{ $fine->product ? $fine->product->name : 'Umum' }}</span>
                                                </p>
                                                <p class="text-sm text-gray-500 mb-6">
                                                    Nominal: <span class="font-bold text-red-600">Rp
                                                        {{ number_format($fine->amount, 0) }}</span>
                                                </p>

                                                <div class="grid grid-cols-2 gap-3">
                                                    <button @click="showDeleteModal = false" type="button"
                                                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition-colors">
                                                        Batal
                                                    </button>
                                                    <form action="{{ route('fines.destroy', $fine->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-red-200 transition-transform active:scale-95">
                                                            Ya, Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($rental->additional_cost == 0 && $rental->fines->count() == 0)
                            <p class="text-xs text-gray-400 italic text-center py-2">Tidak ada biaya tambahan atau denda.</p>
                        @endif
                    </div>

                    <!-- Form Additional Cost (Hidden) -->
                    <form id="form-additional-cost" action="{{ route('rentals.updateAdditionalCost', $rental->id) }}"
                        method="POST"
                        class="hidden bg-gray-50 p-3 rounded-lg border border-gray-100 mt-4 animate-fade-in-down">
                        @csrf
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Edit Biaya Tambahan</p>
                        <div class="space-y-2">
                            <div>
                                <input type="number" step="1000" name="additional_cost"
                                    value="{{ $rental->additional_cost }}"
                                    class="w-full text-xs border-gray-200 rounded focus:ring-blue-100 focus:border-blue-300"
                                    placeholder="Nominal" min="0">
                            </div>
                            <div>
                                <input type="text" name="additional_cost_note" value="{{ $rental->additional_cost_note }}"
                                    class="w-full text-xs border-gray-200 rounded focus:ring-blue-100 focus:border-blue-300"
                                    placeholder="Keterangan">
                            </div>
                            <button type="submit"
                                class="w-full bg-blue-600 text-white text-xs font-bold py-1.5 rounded hover:bg-blue-700 transition">Simpan</button>
                        </div>
                    </form>

                    <!-- Form Fine (Hidden) -->
                    <form id="form-fine" action="{{ route('rentals.storeFine', $rental->id) }}" method="POST"
                        class="hidden bg-red-50 p-3 rounded-lg border border-red-100 mt-4 animate-fade-in-down">
                        @csrf
                        <p class="text-[10px] font-bold text-red-400 uppercase mb-2">Tambah Denda Baru</p>
                        <div class="space-y-2">
                            <input type="number" step="1000" name="amount"
                                class="w-full text-xs border-gray-200 rounded focus:ring-red-100 focus:border-red-300"
                                placeholder="Nominal Denda" required min="0">

                            <input type="text" name="note"
                                class="w-full text-xs border-gray-200 rounded focus:ring-red-100 focus:border-red-300"
                                placeholder="Keterangan (mis: rusak)" required>

                            <select name="product_id"
                                class="w-full text-xs border-gray-200 rounded focus:ring-red-100 focus:border-red-300 bg-white">
                                <option value="">-- Pilih Barang (Opsional) --</option>
                                @foreach($rental->items as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->product->name }}</option>
                                @endforeach
                            </select>

                            <button type="submit"
                                class="w-full bg-red-600 text-white text-xs font-bold py-1.5 rounded hover:bg-red-700 transition">Simpan
                                Denda</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- INVOICE WA MODAL -->
    <div id="invoiceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="closeInvoiceModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <!-- WhatsApp Icon -->
                            <svg class="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Generate Pesan WA
                            </h3>
                            <div class="mt-2">
                                <!-- Selection State -->
                                <div id="modalSelection" class="grid grid-cols-1 gap-3">
                                    <button onclick="generateMessage('invoice')"
                                        class="w-full flex items-center p-3 border border-gray-200 rounded-lg hover:bg-pink-50 hover:border-pink-200 transition-all group text-left">
                                        <div
                                            class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3 group-hover:bg-green-200 transition-colors">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">Invoice Lengkap</h4>
                                            <p class="text-xs text-gray-500">Rincian produk, harga, & total.</p>
                                        </div>
                                    </button>

                                    <button onclick="generateMessage('balance')"
                                        class="w-full flex items-center p-3 border border-gray-200 rounded-lg hover:bg-pink-50 hover:border-pink-200 transition-all group text-left">
                                        <div
                                            class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3 group-hover:bg-blue-200 transition-colors">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">Sisa Tagihan</h4>
                                            <p class="text-xs text-gray-500">Info pembayaran & sisa bayar.</p>
                                        </div>
                                    </button>

                                    <button onclick="showCRMOptions()"
                                        class="w-full flex items-center p-3 border border-gray-200 rounded-lg hover:bg-pink-50 hover:border-pink-200 transition-all group text-left">
                                        <div
                                            class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3 group-hover:bg-purple-200 transition-colors">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-sm">CRM & Sapaan</h4>
                                            <p class="text-xs text-gray-500">Pesan personal & follow-up.</p>
                                        </div>
                                    </button>

                                    <!-- CRM Sub-Options (Initially Hidden) -->
                                    <div id="crmSubOptions" class="hidden mt-3 pl-4 space-y-2 border-l-2 border-purple-200">
                                        <button onclick="generateMessage('crm-followup')"
                                            class="w-full flex items-center p-2 rounded-lg hover:bg-purple-50 transition-all text-left">
                                            <div
                                                class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h5 class="text-xs font-semibold text-gray-700">Follow-up Acara</h5>
                                                <p class="text-xs text-gray-500">Tanya kabar & gimana acaranya</p>
                                            </div>
                                        </button>

                                        <button onclick="generateMessage('crm-testimoni')"
                                            class="w-full flex items-center p-2 rounded-lg hover:bg-purple-50 transition-all text-left">
                                            <div
                                                class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h5 class="text-xs font-semibold text-gray-700">Minta Testimoni</h5>
                                                <p class="text-xs text-gray-500">Subtle request untuk review</p>
                                            </div>
                                        </button>

                                        <button onclick="generateMessage('crm-reoffer')"
                                            class="w-full flex items-center p-2 rounded-lg hover:bg-purple-50 transition-all text-left">
                                            <div
                                                class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h5 class="text-xs font-semibold text-gray-700">Penawaran Ulang</h5>
                                                <p class="text-xs text-gray-500">Offer untuk acara berikutnya</p>
                                            </div>
                                        </button>

                                        <button onclick="generateMessage('crm-checkup')"
                                            class="w-full flex items-center p-2 rounded-lg hover:bg-purple-50 transition-all text-left">
                                            <div
                                                class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h5 class="text-xs font-semibold text-gray-700">Check Persiapan</h5>
                                                <p class="text-xs text-gray-500">Tanya kesiapan sebelum acara</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Loading State -->
                                <div id="modalLoading" class="hidden py-8 flex flex-col items-center justify-center">
                                    <svg class="animate-spin h-8 w-8 text-pink-500 mb-2" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <p class="text-sm text-gray-500">Sedang menyusun pesan dengan AI...</p>
                                </div>

                                <!-- Result State -->
                                <div id="modalResult" class="hidden">
                                    <textarea id="invoiceText" rows="10"
                                        class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-xs border-gray-300 rounded-md"
                                        readonly></textarea>
                                </div>

                                <!-- Error State -->
                                <div id="modalError" class="hidden py-4 text-center">
                                    <p class="text-sm text-red-500 font-medium" id="errorMessage"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="copyBtn" onclick="copyToClipboard()"
                        class="hidden w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Salin Pesan
                    </button>
                    <button type="button" onclick="closeInvoiceModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentRentalId = null;

        function openInvoiceModal(rentalId) {
            currentRentalId = rentalId;
            const modal = document.getElementById('invoiceModal');
            const selection = document.getElementById('modalSelection');
            const loading = document.getElementById('modalLoading');
            const result = document.getElementById('modalResult');
            const errorDiv = document.getElementById('modalError');
            const copyBtn = document.getElementById('copyBtn');
            const textarea = document.getElementById('invoiceText');
            const modalTitle = document.getElementById('modal-title');

            modal.classList.remove('hidden');

            // RESET STATE: Show Selection, Hide others
            selection.classList.remove('hidden');
            loading.classList.add('hidden');
            result.classList.add('hidden');
            errorDiv.classList.add('hidden');
            copyBtn.classList.add('hidden');

            modalTitle.innerText = 'Pilih Jenis Pesan WA';
            textarea.value = '';
        }

        function generateMessage(type) {
            // Note: In show.blade.php, rentalId is typically passed or we can use the one from openInvoiceModal.
            // But openInvoiceModal sets currentRentalId global variable.
            //Wait, openInvoiceModal in show.blade.php previously took rentalId.
            // But in SHOW view we might only have ONE rental. 
            // Let's verify if 'currentRentalId' is needed or if we can just use {{ $rental->id }} directly if not passed?
            // However, consistent JS is better.

            if (!currentRentalId) currentRentalId = '{{ $rental->id }}'; // Fallback if opened differently

            const selection = document.getElementById('modalSelection');
            const loading = document.getElementById('modalLoading');
            const result = document.getElementById('modalResult');
            const errorDiv = document.getElementById('modalError');
            const copyBtn = document.getElementById('copyBtn');
            const textarea = document.getElementById('invoiceText');
            const modalTitle = document.getElementById('modal-title');

            // UI UPDATES
            selection.classList.add('hidden');
            loading.classList.remove('hidden');

            let titleMap = {
                'invoice': 'Generate Invoice',
                'balance': 'Generate Sisa Tagihan',
                'crm': 'Generate Pesan CRM'
            };
            modalTitle.innerText = titleMap[type] || 'Generate Pesan';

            // CALL API
            fetch(`/rentals/${currentRentalId}/generate-invoice`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ type: type })
            })
                .then(response => response.json())
                .then(data => {
                    loading.classList.add('hidden');
                    if (data.message) {
                        result.classList.remove('hidden');
                        copyBtn.classList.remove('hidden');
                        textarea.value = data.message;
                    } else {
                        errorDiv.classList.remove('hidden');
                        document.getElementById('errorMessage').innerText = data.error || 'Terjadi kesalahan.';
                    }
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    errorDiv.classList.remove('hidden');
                    document.getElementById('errorMessage').innerText = 'Gagal menghubungi server.';
                    console.error(err);
                });
        }

        function showCRMOptions() {
            const crmSubOptions = document.getElementById('crmSubOptions');
            crmSubOptions.classList.toggle('hidden');
        }

        function closeInvoiceModal() {
            document.getElementById('invoiceModal').classList.add('hidden');
            // Reset CRM sub-options
            document.getElementById('crmSubOptions').classList.add('hidden');
        }

        function copyToClipboard() {
            const copyText = document.getElementById("invoiceText");
            copyText.select();
            copyText.setSelectionRange(0, 99999); /* For mobile devices */
            navigator.clipboard.writeText(copyText.value).then(() => {
                const btn = document.getElementById('copyBtn');
                const originalText = btn.innerText;
                btn.innerText = 'Disalin!';
                setTimeout(() => {
                    btn.innerText = originalText;
                }, 2000);
            });
        }
    </script>

    <!-- Edit Note Modal -->
    <div id="noteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" onclick="closeNoteModal()"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <form action="{{ route('rentals.updateNotes', $rental->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-pink-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Edit Catatan
                                    Rental</h3>
                                <div class="mt-2">
                                    <textarea name="notes" rows="5"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 sm:text-sm"
                                        placeholder="Tambahkan catatan untuk rental ini...">{{ $rental->notes }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-pink-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 sm:ml-3 sm:w-auto">Simpan</button>
                        <button type="button" onclick="closeNoteModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CANCEL CONFIRMATION MODAL -->
    <div id="cancelModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="closeCancelModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="{{ route('rentals.cancel', $rental->id) }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">
                                    Batalkan Pesanan?
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat
                                        dibatalkan.
                                    </p>
                                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
                                        <p class="text-xs text-red-700 font-medium">
                                            <strong>Kode Transaksi:</strong> {{ $rental->code }}
                                        </p>
                                        <p class="text-xs text-red-700 font-medium mt-1">
                                            <strong>Customer:</strong> {{ $rental->customer->name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Ya, Batalkan
                        </button>
                        <button type="button" onclick="closeCancelModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Tidak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- STATUS UPDATE MODAL -->
    <div id="statusModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true" x-data="{ 
                                            currentStatus: '{{ $rental->status }}',
                                            selectedStatus: '{{ $rental->status }}',
                                            needsDP() { return this.currentStatus === 'waiting' && this.selectedStatus === 'booked'; }
                                        }">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="closeStatusModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="{{ route('rentals.updateStatus', $rental->id) }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">
                                    Update Status Rental
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Pilih status baru untuk rental <strong>{{ $rental->code }}</strong>
                                    </p>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Status Rental</label>
                                        <select name="status" x-model="selectedStatus" required
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="waiting" {{ $rental->status === 'waiting' ? 'selected' : '' }}>
                                                Menunggu Konfirmasi</option>
                                            <option value="booked" {{ $rental->status === 'booked' ? 'selected' : '' }}>Booked
                                            </option>
                                            <option value="rented" {{ $rental->status === 'rented' ? 'selected' : '' }}>Disewa
                                            </option>
                                            <option value="returned" {{ $rental->status === 'returned' ? 'selected' : '' }}>
                                                Dikembalikan</option>
                                            <option value="completed" {{ $rental->status === 'completed' ? 'selected' : '' }}>
                                                Selesai</option>
                                            <option value="canceled" {{ $rental->status === 'canceled' ? 'selected' : '' }}>
                                                Dibatalkan</option>
                                        </select>
                                    </div>

                                    <!-- DP Input (Conditional - only show if transitioning from waiting to booked) -->
                                    <div x-show="needsDP()" x-transition class="mt-4 space-y-3" style="display: none;"
                                        x-data="{ dpAmount: {{ $rental->down_payment ?? 0 }}, grandTotal: {{ $rental->total_fee + $rental->shipping_cost }} }">
                                        <label class="block text-sm font-medium text-gray-700">Uang Muka/DP <span
                                                class="text-red-500">*</span></label>

                                        <!-- Quick Fill Buttons -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <button type="button" @click="dpAmount = Math.round(grandTotal * 0.5)"
                                                class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all shadow-md active:scale-95 flex flex-col items-center gap-1">
                                                <span class="text-lg font-bold">50%</span>
                                                <span class="text-xs opacity-90">Rp
                                                    {{ number_format(($rental->total_fee + $rental->shipping_cost) * 0.5, 0, ',', '.') }}</span>
                                            </button>
                                            <button type="button" @click="dpAmount = grandTotal"
                                                class="px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all shadow-md active:scale-95 flex flex-col items-center gap-1">
                                                <span class="text-lg font-bold">100%</span>
                                                <span class="text-xs opacity-90">Rp
                                                    {{ number_format($rental->total_fee + $rental->shipping_cost, 0, ',', '.') }}</span>
                                            </button>
                                        </div>

                                        <input type="number" name="down_payment" x-model="dpAmount" min="0" step="1000"
                                            placeholder="Masukkan nominal DP"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                            :required="needsDP()">
                                        <p class="text-xs text-gray-500">DP wajib diisi untuk mengkonfirmasi booking</p>
                                    </div>

                                    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
                                        <p class="text-xs text-blue-700">
                                            <strong>Status Saat Ini:</strong>
                                            <span class="font-bold">{{ $statusLabel }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                            Update Status
                        </button>
                        <button type="button" onclick="closeStatusModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        function openNoteModal() {
            document.getElementById('noteModal').classList.remove('hidden');
        }

        function closeNoteModal() {
            document.getElementById('noteModal').classList.add('hidden');
        }

        function openCancelModal() {
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        function openStatusModal() {
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
    </script>
@endsection