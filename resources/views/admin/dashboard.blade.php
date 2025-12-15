@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Proses Pesanan Baru</h2>
        <p class="text-sm text-gray-600">Salin pesan dari WhatsApp dan tempel di sini untuk diproses.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-1">
                <form action="#" method="POST"> @csrf
                    <div class="p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Isi Chat WhatsApp:</label>
                        <textarea
                            name="raw_message"
                            rows="12"
                            class="w-full rounded-lg border-gray-300 focus:border-pink-500 focus:ring-pink-500 font-mono text-sm bg-gray-50 p-4"
                            placeholder="Contoh:&#10;Halo kak mau sewa&#10;Nama: Kennan&#10;Tgl: 12 Des&#10;Alat: Kursi Tiffany 20pcs..."
                        ></textarea>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 rounded-b-xl flex justify-end border-t border-gray-100">
                        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-medium shadow-sm transition-all">
                            Proses Data &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-4">

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                <h4 class="font-bold text-blue-800 text-sm mb-2">Panduan Cepat:</h4>
                <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                    <li>Copy seluruh teks chat dari WA.</li>
                    <li>Paste ke kolom di samping.</li>
                    <li>Klik tombol <b>Proses Data</b>.</li>
                    <li>Sistem akan mendeteksi nama & barang.</li>
                </ul>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <h4 class="font-bold text-gray-800 text-sm mb-3">Stok Menipis</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Kursi Tiffany</span>
                        <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold">Habis</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Meja Bulat</span>
                        <span class="bg-yellow-100 text-yellow-600 px-2 py-0.5 rounded-full font-bold">Sisa 5</span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection