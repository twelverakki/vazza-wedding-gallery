@extends('layouts.admin')

@section('header')
    <header class="h-16 flex items-center justify-between px-4 sticky top-0 z-10">
        <!-- Input Booking Button -->
        <button @click="$dispatch('open-parser-modal')"
            class="hidden sm:flex items-center gap-2 bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-full text-sm font-bold transition-all transform hover:scale-105">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Input Booking
        </button>
        <!-- Mobile Input Icon Only -->
        <button @click="$dispatch('open-parser-modal')"
            class="sm:hidden flex items-center justify-center w-10 h-10 text-black rounded-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
        
        <div>
            <h2 class="text-xl font-bold text-gray-800">Transaksi Baru</h2>
        </div>

        <div class="flex items-center">
            <a href="{{ route('rentals.index') }}"
                class="text-gray-600 hover:text-pink-600 p-2 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>
    </header>
@endsection

@section('content')
    <div class="max-w-5xl mx-auto">
        <style>
            #selected-items-body {
                counter-reset: row-num;
            }

            #selected-items-body tr {
                counter-increment: row-num;
            }

            .row-num::before {
                content: counter(row-num);
            }
        </style>
        <form action="{{ route('rentals.store') }}" method="POST" class="space-y-6 relative" x-data="{ loading: false }" @submit="loading = true">
            
            <!-- Loading Overlay -->
            <div x-show="loading" class="absolute inset-0 z-50 bg-white/80 backdrop-blur-sm rounded-xl flex flex-col items-center justify-center transition-all h-full min-h-[500px]"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="display: none;">
                <div class="sticky top-1/2 transform -translate-y-1/2 flex flex-col items-center">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-pink-200 border-t-pink-600 rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-6 h-6 text-pink-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </div>
                    <p class="mt-4 font-bold text-gray-800 text-lg animate-pulse">Menyimpan Transaksi...</p>
                    <p class="text-sm text-gray-500">Jangan tutup halaman ini ya.</p>
                </div>
            </div>

            @csrf

            {{-- Hidden inputs for persistent parsing info --}}
            @if(session('parsed_raw_text') || old('parsed_raw_text'))
                <input type="hidden" name="parsed_raw_text" value="{{ session('parsed_raw_text') ?? old('parsed_raw_text') }}">
            @endif
            
            @if(session('parsed_unmatched') || old('parsed_unmatched'))
                @php $unmatched = session('parsed_unmatched') ?? old('parsed_unmatched'); @endphp
                @if(is_array($unmatched))
                    @foreach($unmatched as $item)
                        <input type="hidden" name="parsed_unmatched[]" value="{{ $item }}">
                    @endforeach
                @endif
            @endif

            <!-- Parsing Info Section -->
            @if(session('parsed_raw_text') || session('parsed_unmatched') || old('parsed_raw_text') || old('parsed_unmatched'))
                <div id="parsing-info-container"
                    class="bg-blue-50 rounded-lg p-5 border border-blue-100 mb-6 space-y-3 transition-all duration-300 origin-top">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="font-bold text-blue-800">Info Hasil Parsing</h3>
                        </div>

                        <button type="button" onclick="toggleStickyInfo()"
                            class="p-1.5 rounded-md text-blue-400 hover:text-blue-600 hover:bg-blue-100 transition-colors group relative"
                            title="Pin/Unpin Info">
                            <svg id="pin-icon-off" class="w-5 h-5 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-10h4m-4-6v6L9 3V9"></path>
                            </svg>
                            <svg id="pin-icon-on" class="w-5 h-5 hidden rotate-45" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 12V4H8v8c0 2.21-1.79 4-4 4v2h16v-2c-2.21 0-4-1.79-4-4z"></path>
                            </svg>
                        </button>
                    </div>

                    @php 
                        $unmatchedList = session('parsed_unmatched') ?? old('parsed_unmatched');
                    @endphp
                    @if($unmatchedList && count($unmatchedList) > 0)
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-xl">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-bold text-red-800">
                                        Barang Tidak Dikenal
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach($unmatchedList as $unknown)
                                                <li>{{ $unknown }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php 
                        $rawText = session('parsed_raw_text') ?? old('parsed_raw_text'); 
                    @endphp
                    @if($rawText)
                        <details class="group/details">
                            <summary
                                class="flex items-center gap-2 cursor-pointer text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
                                <span>Lihat Teks Asli</span>
                                <svg class="w-4 h-4 transition-transform group-open/details:rotate-180" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </summary>
                            <div
                                class="mt-2 p-3 bg-white rounded-md border border-blue-100 text-xs font-mono text-gray-600 whitespace-pre-wrap max-h-60 overflow-y-auto">
                                {{ $rawText }}
                            </div>
                        </details>
                    @endif
                </div>

                <script>
                    function toggleStickyInfo() {
                        const container = document.getElementById('parsing-info-container');
                        const iconOff = document.getElementById('pin-icon-off');
                        const iconOn = document.getElementById('pin-icon-on');

                        container.classList.toggle('sticky');
                        container.classList.toggle('top-4');
                        container.classList.toggle('z-40');
                        container.classList.toggle('shadow-2xl');
                        container.classList.toggle('border-blue-300');

                        if (container.classList.contains('sticky')) {
                            iconOff.classList.add('hidden');
                            iconOn.classList.remove('hidden');
                        } else {
                            iconOff.classList.remove('hidden');
                            iconOn.classList.add('hidden');
                        }
                    }
                </script>
            @endif

            <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-6 h-6 bg-pink-50 text-pink-600 rounded flex items-center justify-center text-xs font-bold border border-pink-100">1</span>
                        Data Pelanggan
                    </h3>
                    <button type="button" onclick="openCustomerModal()"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-pink-600 bg-pink-50 hover:bg-pink-100 border border-pink-200 rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        Pilih dari Database
                    </button>
                    <div class="hidden md:block">
                        <span class="text-[10px] font-semibold text-pink-500 bg-pink-50 px-2 py-0.5 rounded border border-pink-100 uppercase tracking-widest">Informasi Kontak</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <div class="space-y-1">
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-700 ml-1">
                            Nama Lengkap
                        </label>
                        <input type="text" name="customer_name"
                            value="{{ session('parsed_data.customer_name') ?? old('customer_name') }}"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all outline-none placeholder:text-gray-400"
                            placeholder="Masukkan nama pengantin/klien" required>
                    </div>

                    <div class="space-y-1">
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-700 ml-1">
                            No. HP / WhatsApp
                        </label>
                        <input type="text" name="customer_phone"
                            value="{{ session('parsed_data.customer_phone') ?? old('customer_phone') }}"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all outline-none placeholder:text-gray-400"
                            placeholder="0812xxxx" required>
                    </div>

                    <div class="md:col-span-2 space-y-1">
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-700 ml-1">
                            Alamat KTP / Domisili
                        </label>
                        <textarea name="customer_address" rows="2" id="customer_address"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all outline-none placeholder:text-gray-400"
                            required
                            placeholder="Alamat lengkap pelanggan">{{ session('parsed_data.customer_address') ?? old('customer_address') }}</textarea>
                    </div>

                    <div class="md:col-span-2 space-y-1 pt-2">
                        <div class="flex items-center justify-between ml-1">
                            <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                                Alamat Pengiriman / Lokasi Acara
                            </label>
                            <div class="flex items-center gap-2 bg-gray-100 px-2 py-1 rounded border border-gray-200 cursor-pointer hover:bg-gray-200 transition-colors"
                                id="btn_copy_address">
                                <input type="checkbox" id="same_address"
                                    class="w-3 h-3 rounded border-gray-400 text-pink-500 focus:ring-pink-500 cursor-pointer">
                                <label for="same_address"
                                    class="text-[10px] font-semibold text-gray-600 cursor-pointer">Sama
                                    dengan alamat pelanggan</label>
                            </div>
                        </div>
                        <textarea name="shipping_address" rows="2" id="shipping_address"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all outline-none placeholder:text-gray-400"
                            placeholder="Kosongkan jika diambil sendiri di gallery">{{ old('shipping_address') }}</textarea>
                    </div>
                </div>
            </div>

            <script>
                document.getElementById('same_address').addEventListener('change', function () {
                    const customerAddress = document.getElementById('customer_address').value;
                    const shippingAddressField = document.getElementById('shipping_address');

                    if (this.checked) {
                        shippingAddressField.value = customerAddress;
                        shippingAddressField.classList.add('bg-pink-50/20');
                    } else {
                        shippingAddressField.value = '';
                        shippingAddressField.classList.remove('bg-pink-50/20');
                    }
                });
            </script>

            <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-200">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <span
                        class="w-6 h-6 bg-pink-50 text-pink-600 rounded flex items-center justify-center text-xs font-bold border border-pink-100">2</span>
                    Waktu, Jaminan & Biaya
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tgl Pemakaian</label>
                        <input type="date" name="start_date" id="start_date"
                            value="{{ session('parsed_data.start_date') ?? old('start_date') }}"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tgl Pengembalian</label>
                        <input type="date" name="due_date" id="due_date"
                            value="{{ session('parsed_data.due_date') ?? old('due_date') }}"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500"
                            required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Pembayaran</label>
                        <select name="payment_method"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                            <option value="Transfer">Transfer</option>
                            <option value="Cash">Tunai</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Ongkos Kirim</label>
                        <div x-data="currencyInput('shipping_cost', '{{ old('shipping_cost', 0) }}')">
                            <input type="hidden" name="shipping_cost" :value="value">
                            <input type="text" :value="display" @input="input($event)" @keydown.up.prevent="increment" @keydown.down.prevent="decrement" inputmode="numeric"
                                class="w-full rounded-md bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 @error('shipping_cost') border-red-500 text-red-900 @else border-gray-300 @enderror"
                                placeholder="Rp 0">
                            <x-input-error :messages="$errors->get('shipping_cost')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Diskon (Potongan Harga)</label>
                        <div x-data="currencyInput('discount_order', '{{ old('discount_order', 0) }}')">
                            <input type="hidden" name="discount_order" :value="value">
                            <input type="text" :value="display" @input="input($event)" @keydown.up.prevent="increment" @keydown.down.prevent="decrement" inputmode="numeric"
                                class="w-full rounded-md bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 @error('discount_order') border-red-500 text-red-900 @else border-gray-300 @enderror"
                                placeholder="Rp 0">
                            <x-input-error :messages="$errors->get('discount_order')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Biaya Tambahan</label>
                        <div x-data="currencyInput('additional_cost', '{{ old('additional_cost', 0) }}')">
                            <input type="hidden" name="additional_cost" :value="value">
                            <input type="text" :value="display" @input="input($event)" @keydown.up.prevent="increment" @keydown.down.prevent="decrement" inputmode="numeric"
                                class="w-full rounded-md bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 @error('additional_cost') border-red-500 text-red-900 @else border-gray-300 @enderror"
                                placeholder="Rp 0">
                            <x-input-error :messages="$errors->get('additional_cost')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Keterangan Biaya Tambahan <span class="font-normal text-gray-400">(opsional)</span></label>
                        <input type="text" name="additional_cost_note"
                            value="{{ old('additional_cost_note') }}"
                            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all"
                            placeholder="Contoh: biaya antar, servis, dll.">
                        <x-input-error :messages="$errors->get('additional_cost_note')" class="mt-1" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">Down Payment (DP)</label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" id="auto_dp" class="w-3 h-3 rounded border-gray-400 text-pink-500 focus:ring-pink-500">
                                <span class="text-[10px] text-gray-500 font-medium">Auto 50%</span>
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" id="lunas_dp" class="w-3 h-3 rounded border-gray-400 text-green-500 focus:ring-green-500">
                                <span class="text-[10px] text-gray-500 font-medium">Lunas</span>
                            </label>
                        </div>
                        <div x-data="currencyInput('down_payment', '{{ old('down_payment', 0) }}')">
                            <input type="hidden" name="down_payment" :value="value">
                            <input type="text" :value="display" @input="input($event)" @keydown.up.prevent="increment" @keydown.down.prevent="decrement" inputmode="numeric"
                                class="w-full rounded-md bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 @error('down_payment') border-red-500 text-red-900 @else border-gray-300 @enderror"
                                placeholder="Rp 0">
                            <x-input-error :messages="$errors->get('down_payment')" class="mt-1" />
                        </div>
                    </div>

                    <div x-data="{ depositType: '' }" class="col-span-full pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Jaminan</label>
                                <div class="flex gap-4">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="deposit_type" value="KTP" x-model="depositType"
                                            class="hidden peer">
                                        <div
                                            class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs font-semibold text-gray-600 peer-checked:bg-pink-50 peer-checked:text-pink-600 peer-checked:border-pink-300 transition-all">
                                            KTP Asli
                                        </div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="deposit_type" value="Cash" x-model="depositType"
                                            class="hidden peer">
                                        <div
                                            class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs font-semibold text-gray-600 peer-checked:bg-pink-50 peer-checked:text-pink-600 peer-checked:border-pink-300 transition-all">
                                            Tunai (100rb)
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div x-show="depositType === 'Cash'" x-transition>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Jumlah Deposit (IDR)</label>
                                <div x-data="currencyInput('deposit_amount', '{{ old('deposit_amount', 100000) }}')">
                                    <input type="hidden" name="deposit_amount" :value="value">
                                    <input type="text" :value="display" @input="input($event)" @keydown.up.prevent="increment" @keydown.down.prevent="decrement" inputmode="numeric"
                                        class="w-full rounded-md bg-white px-3 py-2 text-sm font-semibold text-pink-600 focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all @error('deposit_amount') border-red-500 text-red-900 @else border-gray-300 @enderror">
                                    <x-input-error :messages="$errors->get('deposit_amount')" class="mt-1" />
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">*Default 100rb untuk Non-KTP</p>
                            </div>

                            <div x-show="depositType && depositType !== ''" x-transition>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Info Tambahan
                                    (Opsional)</label>
                                <input type="text" name="deposit_info"
                                    class="w-full rounded-md border-gray-300 bg-white px-3 py-2 text-sm focus:ring-1 focus:ring-pink-500 focus:border-pink-500 transition-all"
                                    placeholder="No. KTP / Catatan...">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Pilih Barang -->
            <div class="bg-white rounded-lg p-5 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                        <span
                            class="w-6 h-6 bg-pink-50 text-pink-600 rounded flex items-center justify-center text-xs font-bold border border-pink-100">3</span>
                        Pilih Barang
                    </h3>
                    <span id="product-total-display" class="text-sm font-bold text-pink-600 bg-pink-50 px-3 py-1 rounded-full border border-pink-100 shadow-sm">
                        Rp 0
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="hidden md:table-header-group">
                            <tr class="text-xs text-gray-500 uppercase border-b border-gray-200 bg-gray-50/50">
                                <th class="px-3 py-2 font-semibold text-center w-10">No</th>
                                <th class="px-3 py-2 font-semibold">Produk</th>
                                <th class="px-3 py-2 font-semibold text-center">Satuan</th>
                                <th class="px-3 py-2 font-semibold text-center w-32">Qty</th>
                                <th class="px-3 py-2 font-semibold text-center w-32"></th>
                                <th class="px-3 py-2 font-semibold">Total</th>
                                <th class="px-3 py-2 font-semibold text-center w-10">Action</th>
                            </tr>
                        </thead>
                        <tbody id="selected-items-body" class="divide-y divide-gray-50 flex flex-col md:table-row-group gap-4 md:gap-0">
                            @php
                                // Logic for data source priority: Old Input > Session Parsed
                                $detailsSource = []; 
                                $productIds = [];

                                if(old('products')) {
                                    $oldInputs = old('products');
                                    foreach($oldInputs as $item) {
                                        if(isset($item['id'])) {
                                            $pid = $item['id'];
                                            $productIds[] = $pid;
                                            $detailsSource[$pid] = [
                                                'qty' => $item['qty'] ?? 0,
                                                'notes' => $item['notes'] ?? ''
                                            ];
                                        }
                                    }
                                } elseif(session('parsed_items')) {
                                    $parsedSession = session('parsed_items');
                                    $productIds = collect($parsedSession)->pluck('id')->toArray();
                                    foreach($parsedSession as $item) {
                                        $detailsSource[$item['id']] = [
                                            'qty' => $item['qty'] ?? 0,
                                            'notes' => $item['notes'] ?? ''
                                        ];
                                    }
                                }

                                // Fetch Product Models
                                $filteredProducts = $products->whereIn('id', $productIds);
                            @endphp

                            @if(count($filteredProducts) > 0)
                                @foreach($filteredProducts as $product)
                                    @php
                                        // Retrieve Qty from source
                                        $sourceData = $detailsSource[$product->id] ?? ['qty' => 0, 'notes' => ''];
                                        $parsedQty = $sourceData['qty'];
                                        $parsedNotes = $sourceData['notes'] ?? '';

                                        $baseUnit = $product->unit ?? 'Pcs';
                                        
                                        // Step Logic
                                        $step = 1;
                                        $lowerUnit = strtolower($baseUnit);
                                        if (str_contains($lowerUnit, 'lusin') || str_contains($lowerUnit, 'kodi') || str_contains($lowerUnit, 'set')) {
                                            $step = 0.5;
                                        }
                                    @endphp
                                    <tr class="group bg-white md:bg-transparent rounded-xl border border-gray-100 md:border-b md:border-0 md:hover:bg-pink-50/10 transition-colors shadow-sm md:shadow-none p-4 md:p-0 flex flex-col md:table-row relative"
                                        x-data="{
                                            displayQty: {{ $parsedQty > 0 ? $parsedQty : 1 }},
                                            step: {{ $step }},
                                            price: {{ $product->price }},
                                            get total() { return this.displayQty * this.price; },
                                            format(num) {
                                                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            },
                                            increment() {
                                                this.displayQty = parseFloat((this.displayQty + this.step).toFixed(2));
                                            },
                                            decrement() {
                                                if (this.displayQty > this.step) {
                                                    this.displayQty = parseFloat((this.displayQty - this.step).toFixed(2));
                                                } else {
                                                    this.displayQty = this.step;
                                                }
                                            }
                                        }">
                                        <td class="hidden md:table-cell px-3 py-2 text-center text-gray-500 font-medium text-xs row-num"></td>
                                        
                                        <!-- Mobile Header -->
                                        <td class="md:hidden flex justify-between items-start mb-3 border-b border-gray-50 pb-3">
                                             <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                                    @if ($product->image_url)
                                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                    @else
                                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-bold text-gray-800 text-sm">{{ $product->name }}</div>
                                                    <div class="text-xs text-pink-500 font-medium mt-0.5" x-text="format(price) + ' / {{ $product->unit }}'"></div>
                                                    <input type="text" name="products[{{ $loop->index }}][notes]" value="{{ $parsedNotes }}" class="mt-1 w-full text-[10px] border-gray-100 bg-gray-50 rounded px-2 py-1 placeholder:text-gray-300 focus:ring-pink-200 focus:border-pink-300 transition-all" placeholder="Catatan items...">
                                                </div>
                                            </div>
                                            <button type="button" @click="$el.closest('tr').remove()" class="text-gray-400 hover:text-red-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </td>
                                        
                                        <!-- Desktop Product Info -->
                                        <td class="hidden md:table-cell px-3 py-2">
                                            <div class="flex items-center gap-4">
                                                <input type="hidden" name="products[{{ $loop->index }}][id]" value="{{ $product->id }}">
                                                <div class="w-10 h-10 rounded-md bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                                    @if ($product->image_url)
                                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                    @else
                                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-800 text-sm">{{ $product->name }}</div>
                                                    <div class="text-xs text-pink-500 font-medium mt-0.5" x-text="format(price) + ' / {{ $product->unit }}'"></div>
                                                    <input type="text" name="products[{{ $loop->index }}][notes]" value="{{ $parsedNotes }}" class="mt-1 w-40 text-xs border-gray-100 bg-gray-50 rounded px-2 py-1 placeholder:text-gray-300 focus:ring-pink-200 focus:border-pink-300 transition-all" placeholder="Catatan items...">
                                                </div>
                                            </div>
                                        </td>


                                        <td class="px-3 py-2 text-center md:table-cell flex items-center justify-between">
                                            <span class="md:hidden text-xs text-gray-500 font-medium">Satuan</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded border border-pink-100 bg-pink-50 text-pink-600 text-xs font-semibold">{{ $product->unit }}</span>
                                        </td>

                                        <td class="px-3 py-2 md:table-cell flex items-center justify-between gap-4">
                                            <span class="md:hidden text-xs text-gray-500 font-medium">Jumlah</span>
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" @click="decrement()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-pink-100 text-gray-600 hover:text-pink-600 flex items-center justify-center transition-colors font-bold">-</button>
                                                <input type="number" name="products[{{ $loop->index }}][qty]" x-model.number="displayQty" :step="step" :min="step"
                                                    class="w-16 text-center font-bold text-gray-800 border-gray-200 rounded-lg p-1 focus:ring-pink-500 focus:border-pink-500 bg-white shadow-sm">
                                                <button type="button" @click="increment()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-pink-100 text-gray-600 hover:text-pink-600 flex items-center justify-center transition-colors font-bold">+</button>
                                            </div>
                                            <div class="md:hidden hidden"><!-- Hide extra action buttons on mobile --></div>
                                        </td>
                                        
                                        <td class="hidden md:table-cell px-3 py-2 text-center">
                                            <!-- Desktop Action Buttons handled within Qty cell or separate logic if needed -->
                                        </td>
                                        
                                        <td class="px-3 py-2 font-bold text-gray-800 md:table-cell flex items-center justify-between border-t border-gray-50 md:border-0 pt-3 md:pt-0 mt-2 md:mt-0">
                                            <span class="md:hidden text-xs text-gray-500 font-medium">Subtotal</span>
                                            <span x-text="format(total)" class="text-pink-600 md:text-gray-800"></span>
                                        </td>
                                        
                                        <td class="hidden md:table-cell px-4 py-4 text-center">
                                            <button type="button" @click="$el.closest('tr').remove()"
                                                class="w-7 h-7 mx-auto rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all flex items-center justify-center">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button type="button" onclick="openProductModal()"
                        class="w-full py-2 rounded-lg border border-dashed border-gray-300 text-gray-500 hover:border-pink-400 hover:text-pink-600 hover:bg-pink-50/20 transition-all duration-300 flex items-center justify-center gap-2 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Barang
                    </button>
                </div>
            </div>

            <div class="flex justify-end sm:pb-20 md:pb-0">
                <!-- class="bg-gray-800 hover:bg-black text-white px-6 py-2 rounded-md font-semibold text-sm shadow-sm transition-all w-full md:w-auto"> -->
                <button type="submit"
                    class="bg-gray-800 hover:bg-black text-white px-6 py-4 rounded-xl font-semibold text-xl shadow-sm transition-all w-full md:w-auto">

                    Buat Pesanan
                </button>
            </div>
        </form>
    </div>

    <script>
        const allProducts = @json($products);
        
        // Date Logic
        const startDateInput = document.getElementById('start_date');
        const dueDateInput = document.getElementById('due_date');

        startDateInput.addEventListener('change', function () {
            const startDateVal = this.value;
            if (startDateVal) {
                const startDate = new Date(startDateVal);
                startDate.setDate(startDate.getDate() + 1);
                const minDueDate = startDate.toISOString().split('T')[0];
                dueDateInput.min = minDueDate;
                if (dueDateInput.value < minDueDate) dueDateInput.value = minDueDate;
            } else {
                dueDateInput.removeAttribute('min');
            }
        });

        document.getElementById('same_address').addEventListener('change', function () {
            const shipping = document.getElementById('shipping_address');
            if (this.checked) {
                shipping.value = document.getElementById('customer_address').value;
                shipping.classList.add('bg-pink-50/20');
            } else {
                shipping.value = '';
                shipping.classList.remove('bg-pink-50/20');
            }
        });

        // --- ADD PRODUCT LOGIC ---

        function openProductModal() {
            const modal = document.getElementById('productModal');
            const listContainer = document.getElementById('modalProductList');
            listContainer.innerHTML = ''; 

            const currentIds = Array.from(document.querySelectorAll('input[name^="products"][name$="[id]"]'))
                .map(input => parseInt(input.value));

            allProducts.forEach(product => {
                const isSelected = currentIds.includes(product.id);

                // GRID CARD ITEM
                const itemDiv = document.createElement('div');
                itemDiv.className = `relative group border border-gray-100 rounded-2xl p-3 hover:shadow-md transition-all bg-white ${isSelected ? 'opacity-60 grayscale' : ''}`;
                
                const imageHtml = product.image_url
                    ? `<img src="${product.image_url}" class="w-full h-full object-cover">`
                    : `<svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>`;

                itemDiv.innerHTML = `
                    <div class="aspect-square rounded-xl bg-gray-50 overflow-hidden mb-3 relative flex items-center justify-center">
                        ${imageHtml}
                        ${isSelected ? '<div class="absolute inset-0 bg-black/10 flex items-center justify-center"><svg class="w-8 h-8 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>' : ''}
                    </div>
                    <div>
                        <h5 class="font-bold text-gray-800 text-sm leading-tight mb-1 truncate">${product.name}</h5>
                        <p class="text-xs text-pink-500 font-semibold truncate">Rp ${new Intl.NumberFormat('id-ID').format(product.price)} / ${product.unit}</p>
                    </div>
                    <button type="button"
                        onclick="addProductToForm(${product.id})"
                        class="absolute inset-0 w-full h-full cursor-pointer z-10"
                        ${isSelected ? 'disabled' : ''}>
                    </button>
                    ${!isSelected ? `
                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                         <div class="w-6 h-6 rounded-full bg-pink-500 text-white flex items-center justify-center shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                         </div>
                    </div>` : ''}
                `;
                listContainer.appendChild(itemDiv);
            });

            modal.classList.remove('hidden');
            // Reset scroll
            listContainer.scrollTop = 0;
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
        }
        
        // Filter logic remains similar but targets card titles
        function filterModalProducts() {
            const input = document.getElementById('productSearch');
            const filter = input.value.toLowerCase();
            const list = document.getElementById('modalProductList');
            const items = list.getElementsByClassName('group'); // cards have group class

            for (let i = 0; i < items.length; i++) {
                const h5 = items[i].getElementsByTagName("h5")[0];
                const txtValue = h5.textContent || h5.innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    items[i].style.display = "";
                } else {
                    items[i].style.display = "none";
                }
            }
        }

        function addProductToForm(productId) {
            const product = allProducts.find(p => p.id === productId);
            if (!product) return;

            const tbody = document.getElementById('selected-items-body');
            const currentIndex = document.querySelectorAll('input[name^="products"][name$="[id]"]').length;
            const step = (product.unit?.toLowerCase().match(/lusin|kodi|set/)) ? 0.5 : 1;

            const imageHtml = product.image_url
                ? `<img src="${product.image_url}" class="w-full h-full object-cover">`
                : `<svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>`;

            const row = document.createElement('tr');
            // Responsive Card/Row classes
            row.className = "group bg-white md:bg-transparent rounded-xl border border-gray-100 md:border-b md:border-0 md:hover:bg-pink-50/10 transition-colors shadow-sm md:shadow-none p-4 md:p-0 flex flex-col md:table-row relative";
            
            row.setAttribute('x-data', `{
                displayQty: 1,
                step: ${step},
                price: ${product.price},
                get total() { return this.displayQty * this.price; },
                format(num) { return 'Rp ' + num.toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.'); },
                increment() { this.displayQty = parseFloat((this.displayQty + this.step).toFixed(2)); },
                decrement() {
                    if (this.displayQty > this.step) { this.displayQty = parseFloat((this.displayQty - this.step).toFixed(2)); }
                    else { this.displayQty = this.step; }
                }
            }`);

            // New Responsive HTML Structure matching the loop
            row.innerHTML = `
                <td class="hidden md:table-cell px-3 py-2 text-center text-gray-500 font-medium text-xs row-num"></td>
                
                <!-- Mobile Header -->
                <td class="md:hidden flex justify-between items-start mb-3 border-b border-gray-50 pb-3">
                     <div class="flex items-center gap-3">
                        <input type="hidden" name="products[${currentIndex}][id]" value="${product.id}">
                        <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                            ${imageHtml}
                        </div>
                        <div>
                            <div class="font-bold text-gray-800 text-sm">${product.name}</div>
                            <div class="text-xs text-pink-500 font-medium mt-0.5" x-text="format(price) + ' / ${product.unit}'"></div>
                            <input type="text" name="products[${currentIndex}][notes]" class="mt-1 w-full text-[10px] border-gray-100 bg-gray-50 rounded px-2 py-1 placeholder:text-gray-300 focus:ring-pink-200 focus:border-pink-300 transition-all" placeholder="Catatan items...">
                        </div>
                    </div>
                    <button type="button" @click="$el.closest('tr').remove()" class="text-gray-400 hover:text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </td>
                
                <!-- Desktop Product Info -->
                <td class="hidden md:table-cell px-3 py-2">
                    <div class="flex items-center gap-4">
                        <input type="hidden" name="products[${currentIndex}][id]" value="${product.id}">
                        <div class="w-10 h-10 rounded-md bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                            ${imageHtml}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800 text-sm">${product.name}</div>
                            <div class="text-xs text-pink-500 font-medium mt-0.5" x-text="format(price) + ' / ${product.unit}'"></div>
                            <input type="text" name="products[${currentIndex}][notes]" class="mt-1 w-40 text-xs border-gray-100 bg-gray-50 rounded px-2 py-1 placeholder:text-gray-300 focus:ring-pink-200 focus:border-pink-300 transition-all" placeholder="Catatan items...">
                        </div>
                    </div>
                </td>

                <td class="px-3 py-2 text-center md:table-cell flex items-center justify-between">
                    <span class="md:hidden text-xs text-gray-500 font-medium">Satuan</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded border border-pink-100 bg-pink-50 text-pink-600 text-xs font-semibold">${product.unit}</span>
                </td>

                <td class="px-3 py-2 md:table-cell flex items-center justify-between gap-4">
                    <span class="md:hidden text-xs text-gray-500 font-medium">Jumlah</span>
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" @click="decrement()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-pink-100 text-gray-600 hover:text-pink-600 flex items-center justify-center transition-colors font-bold">-</button>
                        <input type="number" name="products[${currentIndex}][qty]" x-model.number="displayQty" :step="step" :min="step"
                            class="w-16 text-center font-bold text-gray-800 border-gray-200 rounded-lg p-1 focus:ring-pink-500 focus:border-pink-500 bg-white shadow-sm">
                        <button type="button" @click="increment()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-pink-100 text-gray-600 hover:text-pink-600 flex items-center justify-center transition-colors font-bold">+</button>
                    </div>
                </td>
                
                <td class="hidden md:table-cell px-3 py-2 text-center"></td>
                
                <td class="px-3 py-2 font-bold text-gray-800 md:table-cell flex items-center justify-between border-t border-gray-50 md:border-0 pt-3 md:pt-0 mt-2 md:mt-0">
                    <span class="md:hidden text-xs text-gray-500 font-medium">Subtotal</span>
                    <span x-text="format(total)" class="text-pink-600 md:text-gray-800"></span>
                </td>
                
                <td class="hidden md:table-cell px-4 py-4 text-center">
                    <button type="button" @click="$el.closest('tr').remove()"
                        class="w-7 h-7 mx-auto rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
            closeProductModal();
        }
    </script>

    <!-- Modal HTML Standardized -->
    <div id="productModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" onclick="closeProductModal()"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-start sm:items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-gray-100">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Pilih Barang</h3>
                            <p class="text-sm text-gray-500">Klik barang untuk menambahkan</p>
                        </div>
                        <button onclick="closeProductModal()" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
                        <div class="relative">
                            <input type="text" id="productSearch" onkeyup="filterModalProducts()" placeholder="Cari nama barang..."
                                class="w-full rounded-xl border-gray-200 bg-white py-3 pl-11 pr-4 text-sm focus:border-pink-500 focus:ring-pink-500 transition-all shadow-sm">
                            <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <!-- Product Grid -->
                    <div class="max-h-[60vh] overflow-y-auto p-6 custom-scrollbar bg-white" id="modalScrollContainer">
                         <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="modalProductList">
                            <!-- Items injected by JS -->
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script>
        function filterModalProducts() {
            const input = document.getElementById('productSearch');
            const filter = input.value.toLowerCase();
            const list = document.getElementById('modalProductList');
            const items = list.getElementsByTagName('div');

            // Iterate over direct children divs (which are the item rows)
            // My structure: div.flex.items-center.justify-between...
            // Actually the direct children are the rows.
            // Let's use direct child selection
            const rows = list.children;
            for (let i = 0; i < rows.length; i++) {
                const h5 = rows[i].querySelector('h5');
                if (h5) {
                    const txtValue = h5.textContent || h5.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('currencyInput', (modelName, initial = 0) => ({
                value: initial,
                display: '',

                init() {
                    // Check if there are old inputs or default values
                    if (this.value) {
                         if (!isNaN(this.value) && this.value !== '') {
                             this.value = parseInt(this.value);
                         }
                         this.formatDisplay();
                    }
                    
                    this.$watch('value', (newValue) => {
                         this.formatDisplay();
                    });

                    // External Update Listener
                    window.addEventListener('set-currency-value', (e) => {
                        if (e.detail.name === modelName) {
                            this.value = parseInt(e.detail.value) || 0;
                            this.formatDisplay(); 
                            // We do NOT dispatch currency-changed here to avoid loops, 
                            // or if we do, we need to ensure updateAutoDP handles it.
                            // But updateAutoDP listens to shipping/discount. Updating DP won't trigger recalculation of DP.
                        }
                    });
                },
                
                formatDisplay() {
                    // Handle empty case
                    if (this.value === '' || this.value === null) {
                        this.display = '';
                        return;
                    }

                    // Handle invalid number case (garbage input)
                    if (isNaN(this.value)) {
                        this.display = this.value; // Show the garbage text
                        return;
                    }
                    
                    // Manual formatting for consistency (Rp 1.000.000)
                    let formatted = this.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                    this.display = 'Rp ' + formatted;
                },

                increment() {
                    if (isNaN(this.value)) { this.value = 0; }
                    this.value = (parseInt(this.value) || 0) + 1000;
                    this.formatDisplay();
                },

                decrement() {
                     if (isNaN(this.value)) { this.value = 0; }
                    let newValue = (parseInt(this.value) || 0) - 1000;
                    this.value = newValue < 0 ? 0 : newValue;
                    this.formatDisplay();
                },
                
                input(e) {
                    let raw = e.target.value;
                    let digits = raw.replace(/\D/g, '');
                    
                    if (digits === '') {
                        if (raw.trim() === '') {
                             this.value = 0;
                             this.display = '';
                        } else {
                             this.value = raw;
                             this.display = raw;
                        }
                    } else {
                        this.value = parseInt(digits, 10);
                        this.formatDisplay();
                    }
                    this.$dispatch('currency-changed', { name: modelName, value: this.value });
                }
            }));

            // --- AUTO DP & TOTALS LOGIC ---
            const autoDpCheckbox = document.getElementById('auto_dp');
            const lunasDpCheckbox = document.getElementById('lunas_dp');
            const productTotalDisplay = document.getElementById('product-total-display');
            
            function formatCurrency(num) {
                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function calculateValues() {
                // 1. Sum Products
                let productTotal = 0;
                
                const qtyInputs = document.querySelectorAll('input[name^="products"][name$="[qty]"]');
                qtyInputs.forEach(input => {
                    const row = input.closest('tr');
                    const pidInput = row.querySelector('input[name$="[id]"]');
                    if(pidInput) {
                        const pid = parseInt(pidInput.value);
                        // Find price
                        const product = allProducts.find(p => p.id === pid);
                        if(product) {
                            let qty = parseFloat(input.value) || 0;
                            productTotal += qty * product.price;
                        }
                    }
                });

                // 2. Shipping & Discount
                const shippingInput = document.querySelector('input[name="shipping_cost"]');
                const discountInput = document.querySelector('input[name="discount_order"]');
                
                let shipping = parseInt(shippingInput.value) || 0;
                let discount = parseInt(discountInput.value) || 0;

                return {
                    productTotal: productTotal,
                    shipping: shipping,
                    discount: discount,
                    grandTotal: productTotal + shipping - discount
                };
            }

            function updateUI() {
                const totals = calculateValues();
                
                // Update Product Total Badge
                if(productTotalDisplay) {
                    productTotalDisplay.textContent = formatCurrency(totals.productTotal);
                }

                // Update Auto DP if enabled
                if(autoDpCheckbox.checked || lunasDpCheckbox.checked) {
                    // Formula: (Total Barang - Diskon) * 50%
                    let scale = autoDpCheckbox.checked ? 0.5 : 1;
                    let dpBase = totals.productTotal - totals.discount;
                    if(dpBase < 0) dpBase = 0;
                    
                    const dpValue = Math.floor(dpBase * scale);
                    window.dispatchEvent(new CustomEvent('set-currency-value', { 
                        detail: { name: 'down_payment', value: dpValue } 
                    }));
                }
            }

            // Listeners
            // 1. Auto DP & Lunas Checkbox Toggle
            autoDpCheckbox.addEventListener('change', function() {
                if(this.checked) {
                    lunasDpCheckbox.checked = false;
                    updateUI();
                }
            });

            lunasDpCheckbox.addEventListener('change', function() {
                if(this.checked) {
                    autoDpCheckbox.checked = false;
                    updateUI();
                }
            });

            // 2. Currency Changes (Shipping, Discount)
            window.addEventListener('currency-changed', (e) => {
                const name = e.detail.name;
                if(name === 'shipping_cost' || name === 'discount_order') {
                    updateUI();
                }
            });
            
            // 3. Product Qty Changes
            document.getElementById('selected-items-body').addEventListener('input', (e) => {
                if(e.target.name && e.target.name.includes('[qty]')) {
                    updateUI();
                }
            });
            
            // 4. Product Add/Remove
            const observer = new MutationObserver(() => {
                updateUI();
            });
            observer.observe(document.getElementById('selected-items-body'), { childList: true, subtree: true });

            // Initial calc
            // Use timeout to let Alpine init
            setTimeout(updateUI, 500);

        });
    </script>

    <!-- Customer Selection Modal -->
    <div id="customerModal" class="fixed inset-0 z-50 hidden" aria-labelledby="customer-modal-title" role="dialog"
        aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" onclick="closeCustomerModal()">
        </div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-start sm:items-center justify-center p-4 text-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-gray-100">

                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900" id="customer-modal-title">Pilih Pelanggan</h3>
                            <p class="text-sm text-gray-500">Klik pelanggan untuk mengisi form</p>
                        </div>
                        <button onclick="closeCustomerModal()"
                            class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
                        <div class="relative">
                            <input type="text" id="customerSearch" onkeyup="filterCustomers()"
                                placeholder="Cari nama atau nomor HP..."
                                class="w-full rounded-xl border-gray-200 bg-white py-3 pl-11 pr-4 text-sm focus:border-pink-500 focus:ring-pink-500 transition-all shadow-sm">
                            <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Customer List -->
                    <div class="max-h-[60vh] overflow-y-auto p-6 custom-scrollbar bg-white">
                        <div class="space-y-3" id="customerList">
                            @foreach ($customers as $customer)
                                <div class="customer-item group border border-gray-100 rounded-xl p-4 hover:shadow-md hover:border-pink-200 transition-all bg-white cursor-pointer"
                                    data-customer-id="{{ $customer->id }}"
                                    data-customer-name="{{ e($customer->name) }}"
                                    data-customer-phone="{{ $customer->phone }}"
                                    data-customer-address="{{ e($customer->address) }}"
                                    onclick="selectCustomerFromData(this)">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="w-12 h-12 rounded-full bg-pink-50 flex items-center justify-center flex-shrink-0 group-hover:bg-pink-100 transition-colors">
                                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="font-bold text-gray-800 text-sm mb-1 customer-name">
                                                {{ $customer->name }}</h5>
                                            <p class="text-xs text-gray-500 customer-phone">📱 {{ $customer->phone }}</p>
                                            <p class="text-xs text-gray-400 mt-1 line-clamp-1 customer-address">📍
                                                {{ $customer->address }}</p>
                                            <p class="text-[10px] text-pink-500 mt-1">
                                                {{ $customer->rentals()->count() }} transaksi sebelumnya
                                            </p>
                                        </div>
                                        <div
                                            class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center">
                                            <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Input Booking (Smart Parser) -->
    <div x-data="{ open: false, loading: false }" x-on:open-parser-modal.window="open = true" x-show="open"
        style="display: none;" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">

        <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100"
                    @click.away="open = false">

                    <!-- Header -->
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-pink-100 text-pink-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </span>
                            Input Pesanan
                        </h3>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Loading Overlay (Nested) -->
                    <div x-show="loading"
                        class="absolute inset-0 z-50 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center">
                        <div class="w-12 h-12 border-4 border-pink-200 border-t-pink-600 rounded-full animate-spin mb-4">
                        </div>
                        <p class="font-bold text-gray-800 animate-pulse">Menganalisa...</p>
                    </div>

                    <!-- Form -->
                    <form action="{{ route('rentals.processParse') }}" method="POST" @submit="loading = true">
                        @csrf
                        <div class="p-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Paste Chat Order di sini:</label>
                            <div class="relative group">
                                <textarea name="raw_text" required
                                    class="w-full min-h-[200px] rounded-xl border-gray-200 bg-gray-50 p-4 text-gray-800 focus:ring-2 focus:ring-pink-100 focus:border-pink-300 transition-all font-medium text-sm resize-none"
                                    placeholder="Contoh:&#10;Nama: Budi&#10;Tgl: 12-14 Des&#10;Alat: Kursi Tiffany 20pcs"></textarea>
                                <div class="absolute bottom-3 right-3 pointer-events-none opacity-50">
                                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">AI
                                        Powered</span>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-gray-500 italic">
                                *Otomatis mendeteksi Nama, Tanggal, dan Produk.
                            </p>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-gray-900 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-black sm:ml-3 sm:w-auto transition-all transform active:scale-95">
                                Analisa Pesanan
                            </button>
                            <button type="button" @click="open = false"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allCustomers = @json($customers);

        function openCustomerModal() {
            document.getElementById('customerModal').classList.remove('hidden');
            document.getElementById('customerSearch').value = '';
            filterCustomers();
        }

        function closeCustomerModal() {
            document.getElementById('customerModal').classList.add('hidden');
        }

        function filterCustomers() {
            const input = document.getElementById('customerSearch');
            const filter = input.value.toLowerCase();
            const items = document.getElementsByClassName('customer-item');

            for (let i = 0; i < items.length; i++) {
                const name = items[i].querySelector('.customer-name').textContent.toLowerCase();
                const phone = items[i].querySelector('.customer-phone').textContent.toLowerCase();

                if (name.indexOf(filter) > -1 || phone.indexOf(filter) > -1) {
                    items[i].style.display = "";
                } else {
                    items[i].style.display = "none";
                }
            }
        }

        function selectCustomerFromData(element) {
            // Get data from data attributes
            const id = element.getAttribute('data-customer-id');
            const name = element.getAttribute('data-customer-name');
            const phone = element.getAttribute('data-customer-phone');
            const address = element.getAttribute('data-customer-address');

            // Populate form fields
            document.querySelector('input[name="customer_name"]').value = name;
            document.querySelector('input[name="customer_phone"]').value = phone;
            document.querySelector('textarea[name="customer_address"]').value = address;

            // Add visual feedback
            const nameInput = document.querySelector('input[name="customer_name"]');
            nameInput.classList.add('bg-green-50', 'border-green-300');
            setTimeout(() => {
                nameInput.classList.remove('bg-green-50', 'border-green-300');
            }, 1500);

            // Close modal
            closeCustomerModal();

            // Show success message
            showToast('success', `Data pelanggan "${name}" berhasil dimuat!`);
        }

        // Toast notification function (if not already exists)
        function showToast(type, message) {
            // Check if toast function exists globally, if not create simple alert
            if (typeof window.showToast === 'function') {
                window.showToast(type, message);
            } else {
                // Simple fallback
                const toast = document.createElement('div');
                toast.className = `fixed bottom-6 right-6 z-50 px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white font-semibold animate-fade-in`;
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        }
    </script>
@endsection