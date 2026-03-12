<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Fine;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RentalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RentalController extends Controller
{
    public function index(Request $request)
    {
        $query = Rental::latest();

        // Search by Code or Customer Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Unified Filter Logic - handles both status and special filters
        if ($request->filled('filter') && $request->filter !== 'all') {
            $filter = $request->filter;

            // Check if it's a database status
            if (in_array($filter, ['waiting', 'booked', 'rented', 'returned', 'completed', 'canceled'])) {
                $query->where('status', $filter);
            } else {
                // Handle special filters
                switch ($filter) {
                    case 'unpaid':
                        $query->where('remaining_balance', '>', 0)
                            ->where('status', '!=', 'canceled'); // Exclude canceled rentals
                        break;
                    case 'overdue':
                        $query->where('status', 'rented')
                            ->whereDate('due_date', '<', now());
                        break;
                    case 'returns_today':
                        $query->where('status', 'rented')
                            ->whereDate('due_date', now());
                        break;
                }
            }
        } elseif ($request->filled('status') && $request->status !== 'all') {
            // Fallback support for 'status' parameter (from dashboard cards)
            $status = $request->status;

            if (in_array($status, ['waiting', 'booked', 'rented', 'returned', 'completed', 'canceled'])) {
                $query->where('status', $status);
            }
        }

        // Pagination Limit
        $perPage = $request->input('per_page', 10);
        if ($perPage === 'all') {
            $rentals = $query->paginate($query->count())->withQueryString();
        } else {
            $rentals = $query->paginate((int) $perPage)->withQueryString();
        }

        return view('admin.rentals.index', compact('rentals'));
    }

    public function create()
    {
        // Removed with(['unit', 'displayUnit']) and where('total_stock') check
        $products = Product::all();
        $customers = Customer::latest()->get();
        return view('admin.rentals.create', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required',
            'customer_phone' => 'required',
            'customer_address' => 'required',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.qty' => 'required|numeric|min:0.01',
            'products.*.discount_type' => 'nullable|in:per_unit,fixed',
            'products.*.discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'discount_order' => 'nullable|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'additional_cost_note' => 'nullable|string|max:255',
            'deposit_type' => 'nullable|in:KTP,Cash,Other',
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_info' => 'nullable|string', // Added validation for deposit_info
            'notes' => 'nullable|string',
            'products.*.notes' => 'nullable|string',
            'parsed_raw_text' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $grossTotal = 0;
            $items = [];

            // Hitung total sewa & siapkan item
            // PREVENT DUPLICATES: Group input by ID and sum quantities
            $groupedProducts = collect($request->products)->groupBy('id')->map(function ($rows) {
                // Sum QTY
                $totalQty = $rows->sum('qty');
                // Take other attributes from the first row (assuming uniform price/discount for same item in one order)
                $first = $rows->first();
                $first['qty'] = $totalQty;
                return $first;
            });

            foreach ($groupedProducts as $item) {
                // Skip jika qty 0
                if ($item['qty'] <= 0)
                    continue;

                $product = Product::find($item['id']);

                // --- SYSTEM AVAILABILITY LOCK REMOVED ---

                // Hitung durasi sewa (hari)
                $duration = \Carbon\Carbon::parse($request->start_date)
                    ->diffInDays(\Carbon\Carbon::parse($request->due_date)) + 1; // +1 hitungan hari dimulai

                // Item Financials
                $price = $product->price;
                $discountAmount = $item['discount_amount'] ?? 0;
                $discountType = $item['discount_type'] ?? 'per_unit';
                $discountItem = 0;

                // Hitung Discount Item (Per Unit Per Day Basis)
                if ($discountAmount > 0) {
                    if ($discountType === 'per_unit') {
                        $discountItem = $discountAmount;
                    } else {
                        if ($item['qty'] > 0) {
                            $discountItem = ($discountAmount / $item['qty']);
                        }
                    }
                }

                // Subtotal per item row: (Price - Discount) * Qty * Duration
                $subtotal = ($price - $discountItem) * $item['qty'];
                $grossTotal += $subtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'unit' => $product->unit,
                    'price_at_rental' => $price,
                    'discount_item' => $discountItem,
                    'subtotal' => $subtotal,
                    'mark' => 'normal',
                    'notes' => $item['notes'] ?? null,
                ];

                // Stock decrement logic removed
            }

            // Global Calculation
            $discountOrder = $request->discount_order ?? 0;

            // VALIDATION: Discount cannot exceed Gross Total
            if ($discountOrder > $grossTotal) {
                return back()->withInput()->with('error', 'Diskon (Rp ' . number_format($discountOrder) . ') tidak boleh lebih besar dari Total Barang (Rp ' . number_format($grossTotal) . ').');
            }

            // Total Fee (Net Rental Price) = Gross - Discount
            $totalFee = $grossTotal - $discountOrder;

            // Calculate Expected Grand Total for DP Validation
            $shipping = $request->shipping_cost ?? 0;
            $additionalCost = $request->additional_cost ?? 0;
            $grandTotal = $totalFee + $shipping + $additionalCost;

            // VALIDATION: DP cannot exceed Grand Total
            $dpInput = $request->down_payment ?? 0;
            if ($dpInput > $grandTotal) {
                return back()->withInput()->with('error', 'Uang Muka/DP (Rp ' . number_format($dpInput) . ') tidak boleh melebihi Total Tagihan (Rp ' . number_format($grandTotal) . ').');
            }

            // Find or Create Customer
            $customer = Customer::firstOrCreate(
                ['name' => $request->customer_name, 'phone' => $request->customer_phone],
                ['address' => $request->customer_address]
            );

            $rental = Rental::create([
                'code' => 'TMP-' . time(), // Placeholder, updated by model event
                'customer_id' => $customer->id,
                'shipping_address' => $request->shipping_address ?? $request->customer_address,
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'payment_method' => $request->payment_method,
                'shipping_cost' => $shipping,
                'additional_cost' => $additionalCost,
                'additional_cost_note' => $request->additional_cost_note,
                'down_payment' => $dpInput,
                'paid_amount' => $dpInput,
                'total_gross' => $grossTotal,
                'discount_order' => $discountOrder,
                'total_fee' => $totalFee,
                'status' => 'waiting',
                'deposit_type' => $request->deposit_type,
                'deposit_amount' => ($request->deposit_type === 'Cash' && !$request->deposit_amount) ? 100000 : ($request->deposit_amount ?? 0),
                'deposit_info' => $request->deposit_info,
                'notes' => $request->notes,
                'raw_wa_text' => $request->parsed_raw_text,
            ]);

            $rental->items()->createMany($items);
            $rental->recalculateBalance(); // Hitung sisa bayar awal

            DB::commit();
            return redirect()->route('rentals.index')->with('success', 'Transaksi sewa berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Rental $rental)
    {
        $rental->load(['items.product', 'fines', 'customer']);

        // Customer Facts Calculation
        $customer = $rental->customer;
        $customerStats = [
            'total_rentals' => $customer->rentals()->count(),
            'total_spend' => $customer->rentals()->sum('total_fee'),
            'last_rental' => $customer->rentals()
                ->orderBy('start_date', 'desc')
                ->first()?->start_date
        ];

        return view('admin.rentals.show', compact('rental', 'customerStats'));
    }

    public function updateAdditionalCost(Request $request, Rental $rental)
    {
        $request->validate([
            'additional_cost' => 'required|numeric|min:0',
            'additional_cost_note' => 'nullable|string|max:255',
        ]);

        $rental->additional_cost = $request->additional_cost;
        $rental->additional_cost_note = $request->additional_cost_note;
        $rental->save(); // Save first

        $rental->recalculateBalance(); // Then recalculate totals

        return back()->with('success', 'Biaya tambahan berhasil diperbarui.');
    }



    // Update mark per item (Normal/Lost/Damaged)
    public function updateItemMark(Request $request, RentalItem $item)
    {
        $request->validate([
            'mark' => 'required|in:normal,lost,damaged',
        ]);

        try {
            $item->update(['mark' => $request->mark]);
            return back()->with('success', 'Mark barang berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update mark: ' . $e->getMessage());
        }
    }

    public function bulkUpdateItemMark(Request $request, Rental $rental)
    {
        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:rental_items,id',
            'mark' => 'required|in:normal,lost,damaged',
        ]);

        try {
            // Update only items belonging to this rental
            $rental->items()->whereIn('id', $request->item_ids)->update(['mark' => $request->mark]);
            return back()->with('success', 'Mark ' . count($request->item_ids) . ' item berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update mark massal: ' . $e->getMessage());
        }
    }

    public function updateNotes(Request $request, Rental $rental)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $rental->update(['notes' => $request->notes]);
            return back()->with('success', 'Catatan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui catatan: ' . $e->getMessage());
        }
    }

    // Tambah Denda
    public function storeFine(Request $request, Rental $rental)
    {
        // ... (No changes needed here unless product validation fails, but exists:products,id is fine)
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'note' => 'required|string',
            'product_id' => 'nullable|exists:products,id'
        ]);

        $rental->fines()->create([
            'amount' => $request->amount,
            'note' => $request->note,
            'product_id' => $request->product_id,
        ]);

        // Recalculate financial
        $rental->recalculateBalance();
        $rental->updateStatus();

        return back()->with('success', 'Denda berhasil ditambahkan');
    }

    // Hapus Denda
    public function destroyFine(Fine $fine)
    {
        try {
            $rental = $fine->rental;
            $fine->delete();

            // Recalculate financial
            $rental->recalculateBalance();
            $rental->updateStatus();

            return back()->with('success', 'Denda berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus denda: ' . $e->getMessage());
        }
    }

    // Pelunasan Manual
    public function markAsPaid(Rental $rental)
    {
        // Set Paid Amount to Total Obligation to clear balance
        $totalObligation = $rental->total_fee + $rental->shipping_cost + $rental->total_fines + $rental->additional_cost;
        $rental->paid_amount = $totalObligation;

        $rental->recalculateBalance(); // This will calculate remaining balance = 0
        $rental->updateStatus(); // Check if status needs to change to completed

        return back()->with('success', 'Pembayaran pelunasan berhasil dicatat.');
    }

    // Cancel Rental
    public function cancel(Rental $rental)
    {
        // Only allow canceling if not already canceled or completed
        if (in_array($rental->status, ['canceled', 'completed'])) {
            return back()->with('error', 'Pesanan ini tidak dapat dibatalkan.');
        }

        // Hybrid financial handling:
        // - Set remaining_balance to 0 (clear outstanding debt)
        // - Keep paid_amount as is (preserve payment history)
        $rental->remaining_balance = 0;
        $rental->status = 'canceled';
        $rental->save();

        // Mark is not changed when canceling rental

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }

    // Update Rental Status Manually
    public function updateRentalStatus(Request $request, Rental $rental)
    {
        $request->validate([
            'status' => 'required|in:waiting,booked,rented,returned,completed,canceled',
            'down_payment' => 'nullable|numeric|min:0',
        ]);

        try {
            $newStatus = $request->status;
            $oldStatus = $rental->status;

            // Special validation: If transitioning from 'waiting' to 'booked', DP is required
            if ($oldStatus === 'waiting' && $newStatus === 'booked') {
                $dpInput = $request->down_payment ?? $rental->down_payment;

                if ($dpInput <= 0) {
                    return back()->with('error', 'Uang Muka/DP wajib diisi untuk mengkonfirmasi booking.');
                }

                // Calculate grand total for DP validation
                $grandTotal = $rental->total_fee + $rental->shipping_cost;
                if ($dpInput > $grandTotal) {
                    return back()->with('error', 'Uang Muka/DP (Rp ' . number_format($dpInput) . ') tidak boleh melebihi Total Tagihan (Rp ' . number_format($grandTotal) . ').');
                }

                // Update DP and paid_amount
                $rental->down_payment = $dpInput;
                $rental->paid_amount = $dpInput;
            }

            $rental->update(['status' => $newStatus]);

            // If status changed to 'returned' or 'completed', update actual_return_date
            if (in_array($newStatus, ['returned', 'completed']) && !$rental->actual_return_date) {
                $rental->actual_return_date = now();
                $rental->save();
            }

            // Recalculate balance after status change
            $rental->recalculateBalance();

            // AUTO-CORRECTION: If admin chose 'returned' but rental is fully paid, auto-upgrade to 'completed'
            $rental->refresh(); // Reload to get updated remaining_balance
            if ($newStatus === 'returned' && $rental->remaining_balance <= 0) {
                $rental->update(['status' => 'completed']);
                return back()->with('success', 'Status rental berhasil diperbarui ke "Selesai" (sudah lunas).');
            }

            return back()->with('success', 'Status rental berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }

    // --- SMART PARSER ---

    public function parseInput()
    {
        return view('admin.rentals.parse');
    }

    public function processParse(Request $request)
    {
        $text = $request->input('raw_text');
        $apiKeyGemini = env('GEMINI_API_KEY');
        $apiKeyGroq = env('GROQ_API_KEY');

        if (!$apiKeyGemini && !$apiKeyGroq) {
            return back()->with('error', 'API Key (Gemini/Groq) belum diset di .env');
        }

        // 1. Context Information
        $productList = Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit
            ];
        })->toJson();

        // 2. Build Prompt
        $prompt = "Tolong ekstrak data dari teks pesanan persewaan ini. Saya akan memberikan daftar produk resmi saya sebagai referensi.
            
            Aturan Ekstraksi:
            - Cocokkan item yang ada di teks dengan 'Daftar Produk Resmi'. Berikan ID produk yang paling sesuai (fuzzy match).
            - Hitung total Quantity secara numerik (angka/desimal).
            - JANGAN dikalikan 12 atau 20. Biarkan sesuai satuan unitnya.
            - '1 lusin' = 1.
            - 'setengah lusin' / 'stengah' = 0.5.
            - '1 kodi' = 1.
            - Jika angka ada di awal atau di akhir baris (misal: '1. Karpet 2'), ambil angka yang merujuk pada jumlah barang (2).
            - Ekstrak Nama Pelanggan, No HP (bersihkan karakter non-angka), Alamat Lengkap, dan Tanggal Acara (format YYYY-MM-DD).
            - **PENTING**: Jika ada item di teks yang TIDAK cocok dengan produk manapun di daftar resmi, masukkan ke list 'unmatched_items'.
            - Berikan output HANYA dalam format JSON murni tanpa markdown.

            Format JSON yang diharapkan:
            {
            \"customer_name\": \"...\",
            \"customer_phone\": \"...\",
            \"customer_address\": \"...\",
            \"start_date\": \"YYYY-MM-DD\",
            \"items\": [
                { \"id\": 123, \"qty\": 10 }
            ],
            \"unmatched_items\": [ \"Item A\", \"Item B\" ]
            }

            Daftar Produk Resmi: {$productList}

            Teks Pesanan WA:
            {$text}";

        $generatedText = null;
        $generatorSuccess = null;

        // 3. Attempt Gemini
        // 3. Attempt Gemini (Primary & Alternate Models)
        if ($apiKeyGemini) {
            $geminiModels = [
                "gemini-2.0-flash-lite-preview-02-05", // Primary
                "gemini-1.5-flash", // Fallback
            ];

            foreach ($geminiModels as $model) {
                if ($generatorSuccess) {
                    break;
                }
                try {
                    $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKeyGemini}";

                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($url, [
                            'contents' => [
                                ['parts' => [['text' => $prompt]]]
                            ]
                        ]);

                    if ($response->successful()) {
                        $jsonResponse = $response->json();
                        $candidates = $jsonResponse['candidates'] ?? [];
                        if (!empty($candidates)) {
                            $generatedText = $candidates[0]['content']['parts'][0]['text'] ?? '';
                            $generatorSuccess = 'Gemini';
                            break; // Success, stop trying other models
                        }
                    } else {
                        \Log::warning("Gemini Model {$model} Failed: " . $response->body());
                    }
                } catch (\Exception $e) {
                    \Log::error("Gemini Model {$model} Exception: " . $e->getMessage());
                }
            }
        }

        // 4. Fallback to Groq if Gemini failed
        if (!$generatedText && $apiKeyGroq) {
            try {
                $groqUrl = "https://api.groq.com/openai/v1/chat/completions";
                $responseGroq = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKeyGroq}",
                        'Content-Type' => 'application/json',
                    ])->post($groqUrl, [
                            'model' => 'llama-3.3-70b-versatile',
                            'messages' => [
                                ['role' => 'user', 'content' => $prompt]
                            ],
                            'temperature' => 0.1
                        ]);

                if ($responseGroq->failed()) {
                    dd($responseGroq->json());
                }

                if ($responseGroq->successful()) {
                    $jsonGroq = $responseGroq->json();
                    $generatorSuccess = 'Groq';
                    $generatedText = $jsonGroq['choices'][0]['message']['content'] ?? '';
                } else {
                    \Log::warning('Groq API Failed: ' . $responseGroq->body());
                }
            } catch (\Exception $e) {
                \Log::error('Groq Exception: ' . $e->getMessage());
            }
        }

        if (!$generatedText) {
            return back()->with('error', 'Gagal memproses dengan AI (Gemini & Groq tidak merespon).');
        }

        // 5. Parse JSON Result
        $generatedText = preg_replace('/^```json\s*|\s*```$/', '', trim($generatedText));
        $parsedData = json_decode($generatedText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{.*\}/s', $generatedText, $matches)) {
                $parsedData = json_decode($matches[0], true);
            }
        }

        if (!$parsedData) {
            \Log::error('JSON Decode Failed: ' . $generatedText);
            return back()->with('error', 'Gagal membaca format data dari AI.');
        }

        // Validasi format data minimal
        $data = [
            'customer_name' => $parsedData['customer_name'] ?? '',
            'customer_phone' => $parsedData['customer_phone'] ?? '',
            'customer_address' => $parsedData['customer_address'] ?? '',
            'start_date' => $parsedData['start_date'] ?? null,
            'due_date' => isset($parsedData['start_date']) ? \Carbon\Carbon::parse($parsedData['start_date'])->addDay()->format('Y-m-d') : null,
        ];

        $items = $parsedData['items'] ?? [];
        $unmatchedItems = $parsedData['unmatched_items'] ?? [];

        return redirect()->route('rentals.create')->with([
            'parsed_data' => $data,
            'parsed_items' => $items,
            'parsed_unmatched' => $unmatchedItems,
            'parsed_raw_text' => $text
        ])->with('success', 'Data berhasil diproses dari ' . $generatorSuccess);
    }
    public function edit(Rental $rental)
    {
        $products = Product::all();
        return view('admin.rentals.edit', compact('rental', 'products'));
    }

    public function update(Request $request, Rental $rental)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.qty' => 'required|numeric|min:0.5',
            'payment_method' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'discount_order' => 'nullable|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'additional_cost_note' => 'nullable|string|max:255',
            'deposit_type' => 'nullable|in:KTP,Cash,Other',
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_info' => 'nullable|string',
            'notes' => 'nullable|string',
            'products.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // 1. Update Customer
            $rental->customer->update([
                'name' => $request->customer_name,
                'phone' => $request->customer_phone,
                'address' => $request->customer_address,
            ]);

            // 2. Prepare Calculation Variables
            $duration = \Carbon\Carbon::parse($request->start_date)
                ->diffInDays(\Carbon\Carbon::parse($request->due_date)) + 1;

            $grossTotal = 0;
            $itemsData = collect($request->products)->keyBy('id');
            $existingItems = $rental->items()->get()->keyBy('product_id');

            // 3. Sync Items
            // Delete removed items
            foreach ($existingItems as $pid => $item) {
                if (!$itemsData->has($pid)) {
                    $item->delete();
                }
            }

            // Update or Create items
            foreach ($itemsData as $pid => $data) {
                $qty = $data['qty'];
                $product = Product::find($pid);
                $price = $product->price; // Always refresh price from master? Yes for consistency.

                // Recalculate Subtotal
                // Note: Discount Item logic is skipped in Edit for simplicity unless we add UI for it.
                // Assuming no per-item discount in Edit for now, or assume 0.
                $discountItem = 0;
                $subtotal = ($price - $discountItem) * $qty;
                $grossTotal += $subtotal;

                if ($existingItems->has($pid)) {
                    $existingItems[$pid]->update([
                        'quantity' => $qty,
                        'price_at_rental' => $price,
                        'subtotal' => $subtotal,
                        // Status remains default unless we want to handle partial returns here.
                        // For safety, don't reset status if it is 'returned' / 'lost'.
                        // But if Qty changes?
                        // If Renting 10 -> Returned 5. Now update Qty to 20.
                        // Status handling is complex. 
                        // Simplified: If edit, we assume standard flow.
                        // Better: Keep status as is.
                        // But if we increase quantity? New units are 'rented'.
                        // If we decrease quantity? We can't easily validly decrease if status is mixed.
                        // Let's assume Edit is permitted mostly for "Correction" phase before event.
                        'notes' => array_key_exists('notes', $data) ? $data['notes'] : $existingItems[$pid]->notes,
                    ]);
                } else {
                    $rental->items()->create([
                        'product_id' => $pid,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'price_at_rental' => $price,
                        'unit' => $product->unit,
                        'subtotal' => $subtotal,
                        'mark' => 'normal',
                        'notes' => $data['notes'] ?? null,
                    ]);
                }
            }

            // 4. Update Rental Header
            $discountOrder = $request->discount_order ?? 0;

            // VALIDATION: Discount cannot exceed Gross Total
            if ($discountOrder > $grossTotal) {
                return back()->withInput()->with('error', 'Diskon (Rp ' . number_format($discountOrder) . ') tidak boleh lebih besar dari Total Barang (Rp ' . number_format($grossTotal) . ').');
            }

            $totalFee = $grossTotal - $discountOrder;

            // Calculate Expected Grand Total for DP Validation
            $shipping = $request->shipping_cost ?? 0;
            $additionalCost = $request->additional_cost ?? 0;
            $grandTotal = $totalFee + $shipping + $additionalCost;

            // VALIDATION: DP cannot exceed Grand Total
            $dpInput = $request->down_payment ?? 0;
            if ($dpInput > $grandTotal) {
                return back()->withInput()->with('error', 'Uang Muka/DP (Rp ' . number_format($dpInput) . ') tidak boleh melebihi Total Tagihan (Rp ' . number_format($grandTotal) . ').');
            }

            // Adjust paid_amount based on DP change
            $oldDP = $rental->down_payment;
            $currentPaid = $rental->paid_amount;
            // Assuming paid_amount tracks total paid, changing DP (base) should adjust total paid by same difference
            // unless paid_amount interacts with other logic. 
            // If I increase DP by 50k, paid_amount increases by 50k.
            // If I decrease DP by 50k, paid_amount decreases by 50k.
            $newPaid = $currentPaid + ($dpInput - $oldDP);
            if ($newPaid < $dpInput)
                $newPaid = $dpInput; // Safety net

            $rental->update([
                'shipping_address' => $request->shipping_address ?? $request->customer_address,
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'payment_method' => $request->payment_method,
                'shipping_cost' => $shipping,
                'additional_cost' => $additionalCost,
                'additional_cost_note' => $request->additional_cost_note,
                'down_payment' => $dpInput,
                'paid_amount' => $newPaid,
                'total_gross' => $grossTotal,
                'discount_order' => $discountOrder,
                'total_fee' => $totalFee,
                'deposit_type' => $request->deposit_type,
                'deposit_amount' => ($request->deposit_type === 'Cash' && !$request->deposit_amount) ? 100000 : ($request->deposit_amount ?? 0),
                'deposit_info' => $request->deposit_info,
                'notes' => $request->notes,
            ]);

            $rental->recalculateBalance();
            // Don't auto-update status logic blindly, preserve if possible, 
            // but `recalculateBalance` doesn't change `status` (rented/returned).
            // `updateStatus` does.
            // If we added items, they are 'rented'.
            // If rental was 'returned', adding items makes it mixed?
            // Let's call updateStatus to be safe.
            $rental->updateStatus();

            DB::commit();
            return redirect()->route('rentals.show', $rental->id)->with('success', 'Transaksi berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }
    // --- PDF INVOICE ---
    public function downloadInvoice(Rental $rental)
    {
        $rental->load(['items.product', 'fines', 'customer']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.rentals.invoice', compact('rental'))
            ->setPaper('a4', 'portrait');

        // stream() = preview di browser (bukan force download)
        return $pdf->stream("invoice-{$rental->code}.pdf");
    }

    // --- AI INVOICE GENERATION ---
    public function generateInvoice(Request $request, Rental $rental)
    {
        $apiKeyGemini = env('GEMINI_API_KEY');
        $apiKeyGroq = env('GROQ_API_KEY');

        if (!$apiKeyGemini && !$apiKeyGroq) {
            return response()->json(['error' => 'API Key (Gemini/Groq) belum diset.'], 500);
        }

        $type = $request->input('type', 'invoice'); // invoice, balance, crm

        // 1. Prepare Data
        $customerName = $rental->customer->name;
        $customerPhone = $rental->customer->phone;

        // Common Financial Data
        $shipping = number_format((float) $rental->shipping_cost, 0, ',', '.');
        $discount = number_format((float) $rental->discount_order, 0, ',', '.');
        $additionalCost = number_format((float) $rental->additional_cost, 0, ',', '.');
        $additionalCostNote = $rental->additional_cost_note ?? 'Biaya Tambahan';
        $grandTotalVal = $rental->total_fee + $rental->shipping_cost + $rental->total_fines + $rental->additional_cost; // Ensure consistency
        $grandTotalFormatted = number_format((float) $grandTotalVal, 0, ',', '.');
        $dp = number_format((float) $rental->down_payment, 0, ',', '.');
        $paid = number_format((float) ($rental->paid_amount ?? $rental->down_payment), 0, ',', '.');
        $remaining = number_format((float) $rental->remaining_balance, 0, ',', '.');

        $rentalCode = $rental->code;
        $customerAddress = $rental->shipping_address ?? $rental->customer->address;
        $eventDate = $rental->start_date ? \Carbon\Carbon::parse($rental->start_date)->translatedFormat('d F Y') : '-';
        $dueDate = $rental->due_date ? \Carbon\Carbon::parse($rental->due_date)->translatedFormat('d F Y') : '-';

        // Items List (Only for Invoice)
        $itemsList = "";
        if ($type === 'invoice') {
            foreach ($rental->items as $index => $item) {
                $num = $index + 1;
                $name = $item->product->name;
                $qty = (float) $item->quantity;
                $unit = $item->unit;
                $price = number_format((float) $item->price_at_rental, 0, ',', '.');
                $totalItem = number_format((float) $item->subtotal, 0, ',', '.');
                $itemsList .= "{$num}. {$name} ({$qty} {$unit} x Rp {$price}) : Rp {$totalItem}\n";
            }
        }

        // 2. Build Prompt based on Type
        $prompt = "";

        if ($type === 'invoice') {
            // CONTEXT-AWARE: Determine payment instruction based on status and balance
            $paymentInstruction = "";
            $footerInstruction = "";


            if ($rental->status === 'waiting') {
                // Waiting: Need DP confirmation
                $dpMinimal = ceil($rental->total_fee * 0.5); // 50% DP
                $dpFormatted = number_format($dpMinimal, 0, ',', '.');
                $paymentInstruction = "- Sisa Tagihan: Rp {$remaining}\n            - INSTRUKSI: \"Mohon transfer DP minimal 50% (Rp {$dpFormatted}) untuk konfirmasi booking. Sisa pelunasan dilakukan saat pengantaran.\"";
                $footerInstruction = "- \"Mohon kirimkan bukti transfer DP untuk konfirmasi booking.\"\n            - \"Terima Kasih 🙏\"";
            } elseif ($rental->status === 'completed' || $rental->remaining_balance <= 0) {
                // Completed or Fully Paid: No payment instruction
                $paymentInstruction = "- Sisa Tagihan: Rp 0 (LUNAS ✅)\n            - CATATAN: Tulis \"Pembayaran telah lunas. Terima kasih atas kepercayaan Anda!\"";
                $footerInstruction = "- \"Terima kasih sudah melunasi pembayaran! 🙏\"\n            - \"Semoga acara berjalan lancar dan sukses! ✨\"";
            } elseif ($rental->remaining_balance > 0 && in_array($rental->status, ['booked', 'rented', 'returned'])) {
                // Has remaining balance: Show amount and instruction
                $paymentInstruction = "- Sisa Tagihan: Rp {$remaining}\n            - INSTRUKSI: \"Sisa pelunasan dilakukan saat pengantaran unit di lokasi acara.\"";
                $footerInstruction = "- \"Mohon siapkan sisa pelunasan saat pengantaran.\"\n            - \"Terima Kasih 🙏\"";
            } else {
                // Default fallback
                $paymentInstruction = "- Sisa Tagihan: Rp {$remaining}";
                $footerInstruction = "- \"Terima Kasih 🙏\"";
            }

            // Build cost breakdown - only include non-zero values
            $costBreakdown = "";

            // Ongkir PP
            if ($rental->shipping_cost > 0) {
                $costBreakdown .= "- Ongkir PP: Rp {$shipping}\n            ";
            }

            // Biaya Tambahan
            if ($rental->additional_cost > 0) {
                $costBreakdown .= "- Biaya Tambahan: Rp {$additionalCost}";
                if ($additionalCostNote) {
                    $costBreakdown .= " ({$additionalCostNote})";
                }
                $costBreakdown .= "\n            ";
            }

            // Diskon
            if ($rental->discount_order > 0) {
                $costBreakdown .= "- Diskon: Rp {$discount}\n            ";
            }

            $prompt = "
            Anda adalah Admin Professional dari Vazza Wedding Gallery. Susun pesan WhatsApp konfirmasi pesanan (Invoice) berikut.

            Instruktur & Format WAJIB:
            1. HEADER:
            - Sapaan: \"Halo Kak {$customerName},\"
            - Ucapan: \"Terima kasih telah memercayakan layanan Vazza Wedding Gallery untuk momen spesial Anda ✨.\"

            2. DATA PELANGGAN:
            - Format: \"Nama Lengkap  : [Nama]\"
            - Format: \"Nomor HP      : [Nomor]\"
            - Format: \"Kode Invoice  : {$rentalCode}\"
            - Format: \"Tanggal Acara : {$eventDate}\"
            - Format: \"Alamat Pengiriman: {$customerAddress}\"
            (Pastikan emoji berada SATU BARIS dengan teks label)

            3. DAFTAR PESANAN:
            - Salin daftar ini persis:
            {$itemsList}

            4. RINCIAN BIAYA:
            {$costBreakdown}- Total Tagihan: Rp {$grandTotalFormatted}

            5. STATUS PEMBAYARAN (PENTING - IKUTI PERSIS):
            - Sudah Dibayar: Rp {$paid}
            {$paymentInstruction}

            6. FOOTER:
            {$footerInstruction}

            ---\r\nDATA INPUT:\r\nNama: {$customerName}\r\nHP: {$customerPhone}\r\nStatus Rental: {$rental->status}\r\n---\r\n
            ";
        } elseif ($type === 'balance') {
            // CONTEXT-AWARE: Check if rental is already paid
            if ($rental->remaining_balance <= 0) {
                // Already paid - thank you message instead of reminder
                $prompt = "
                Anda adalah Admin Vazza Wedding Gallery. Buat pesan WhatsApp ucapan terima kasih untuk pelunasan yang sudah dilakukan.

                Format:
                - Sapaan: \"Halo Kak {$customerName} 👋,\"
                - Konteks: \"Transaksi untuk pesanan {$rentalCode} (Acara tgl {$eventDate}).\"
                - Informasi: \"Total Tagihan: Rp {$grandTotalFormatted} - LUNAS ✅\"
                - Penutup: \"Terima kasih sudah melunasi pembayaran! Semoga acara berjalan lancar dan sukses! 🙏✨\"

                Jaga nada tetap ramah, personal, dan penuh apresiasi.
                ";
            } else {
                // Has remaining balance - send reminder
                $prompt = "
                Anda adalah Admin Vazza Wedding Gallery. Buat pesan WhatsApp sopan untuk mengingatkan Sisa Tagihan.

                Format:
                - Sapaan: \"Halo Kak {$customerName} 👋,\"
                - Konteks: \"Berikut rincian sisa tagihan untuk pesanan {$rentalCode} (Acara tgl {$eventDate}).\"
                - Rincian Singkat:
                  Total Tagihan: Rp {$grandTotalFormatted}
                  Sudah Dibayar: Rp {$paid}
                - Sisa Tagihan: Rp {$remaining}
            - Penutup: \"Mohon melakukan pelunasan sebelum atau saat pengantaran. Terima kasih! 🙏\"

                Jaga nada tetap ramah dan professional.
                ";
            }
        } elseif (str_starts_with($type, 'crm-')) {
            // CRM with specific intent
            $intent = str_replace('crm-', '', $type);
            $today = now();
            $eventDateCarbon = $rental->start_date ? \Carbon\Carbon::parse($rental->start_date) : null;
            $daysUntilEvent = $eventDateCarbon ? $today->diffInDays($eventDateCarbon, false) : null;
            $daysPassed = $daysUntilEvent !== null && $daysUntilEvent < 0 ? abs($daysUntilEvent) : 0;

            if ($intent === 'followup') {
                $timeContext = $daysPassed > 0 ? "acara sudah {$daysPassed} hari lalu" : "acara belum dimulai";
                $prompt = "
                Tugas: Buat pesan WhatsApp follow-up pasca acara yang natural dan ramah dalam bahasa Indonesia.
                
                Konteks:
                - Nama customer: {$customerName}
                - Tanggal acara: {$eventDate} ({$timeContext})
                - Kamu adalah CS Vazza Wedding Gallery
                
                Instruksi WAJIB:
                1. Harus BAHASA INDONESIA
                2. Tone casual tapi profesional (seperti chat teman)
                3. 2-3 kalimat saja
                4. Maksimal 2 emoji
                5. Tanya gimana acaranya dengan genuine
                6. JANGAN minta testimoni
                
                Contoh output yang benar:
                'Halo Kak {$customerName}! Gimana acaranya kemarin? Semoga lancar dan sukses yaaa 😊'
                
                PENTING: Output HANYA berisi pesan WhatsApp, JANGAN ada penjelasan lain.
                ";
            } elseif ($intent === 'testimoni') {
                $prompt = "
                Tugas: Buat pesan WhatsApp untuk minta testimoni secara subtle dalam bahasa Indonesia.
                
                Konteks:
                - Nama customer: {$customerName}
                - Tanggal acara: {$eventDate}
                - Kamu adalah CS Vazza Wedding Gallery
                
                Instruksi WAJIB:
                1. Harus BAHASA INDONESIA
                2. Mulai dengan tanya kabar
                3. Subtle request (JANGAN hard-selling)
                4. 3 kalimat maksimal
                5. Maksimal 2 emoji
                
                Contoh output yang benar:
                'Halo Kak {$customerName}! Gimana acaranya kemarin? Kalau berkenan, boleh dong di-share fotonya atau testimoni singkatnya buat kita 😊 Terima kasih yaaa!'
                
                PENTING: Output HANYA berisi pesan WhatsApp, JANGAN ada penjelasan lain.
                ";
            } elseif ($intent === 'reoffer') {
                $prompt = "
                Tugas: Buat pesan WhatsApp untuk re-offer layanan secara soft dalam bahasa Indonesia.
                
                Konteks:
                - Nama customer: {$customerName}
                - Bisnis: Vazza Wedding Gallery - Penyewaan Alat & Perlengkapan
                
                Instruksi WAJIB:
                1. Harus BAHASA INDONESIA
                2. Sapaan hangat dulu
                3. Soft offer (BUKAN promo hard-selling)
                4. 2-3 kalimat
                5. Maksimal 2 emoji
                6. Fokus pada rental/sewa alat, BUKAN dekorasi
                
                Contoh output yang benar:
                'Halo Kak {$customerName}! Lama ga chat nih 😊 Kalau ada acara lagi atau temen/keluarga butuh sewa perlengkapan, jangan ragu hubungi Vazza yaaa. Terima kasih!'
                
                PENTING: Output HANYA berisi pesan WhatsApp, JANGAN ada penjelasan lain.
                ";
            } elseif ($intent === 'checkup') {
                $daysText = $daysUntilEvent > 0 ? "tinggal {$daysUntilEvent} hari lagi" : "sudah dekat";
                $prompt = "
                Tugas: Buat pesan WhatsApp untuk check persiapan pre-event dalam bahasa Indonesia.
                
                Konteks:
                - Nama customer: {$customerName}
                - Tanggal acara: {$eventDate} ({$daysText})
                - Bisnis: Vazza Wedding Gallery - Penyewaan Alat & Perlengkapan
                
                Instruksi WAJIB:
                1. Harus BAHASA INDONESIA
                2. Tanya persiapan dengan caring
                3. Offer bantuan (tambah/kurangi barang rental)
                4. 2-3 kalimat
                5. Maksimal 2 emoji
                
                Contoh output yang benar:
'Halo Kak {$customerName}! Gimana nih persiapannya? Kalau ada alat yang mau ditambah atau dikurangi, kabarin aja yaaa 😊'
                
                PENTING: Output HANYA berisi pesan WhatsApp, JANGAN ada penjelasan lain.
                ";
            } else {
                // Fallback for unknown intent
                $prompt = "
                Tugas: Buat pesan WhatsApp ramah untuk customer dalam bahasa Indonesia.
                
                Konteks:
                - Nama customer: {$customerName}
                - Tanggal acara: {$eventDate}
                - Bisnis: Vazza Wedding Gallery - Penyewaan Alat & Perlengkapan
                
                Instruksi:
                1. Harus BAHASA INDONESIA
                2. Sapaan hangat dan ramah
                3. 2-3 kalimat
                4. Maksimal 2 emoji
                
                Contoh: 'Halo Kak {$customerName}! Terima kasih sudah memilih Vazza. Jangan ragu hubungi kami kalau ada yang ingin ditanyakan yaaa 😊'
                
                Output HANYA pesan WhatsApp.
                ";
            }
        }

        $generatedText = null;

        // 3. Call AI (Gemini)
        if ($apiKeyGemini) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash-lite-preview-02-05:generateContent?key={$apiKeyGemini}";
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);

                if ($response->successful()) {
                    $generatedText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                }
            } catch (\Exception $e) {
                \Log::error('Gemini API Error: ' . $e->getMessage());
            }
        }

        // 4. Fallback (Groq)
        if (!$generatedText && $apiKeyGroq) {
            // ... Similar logic to processParse ...
            try {
                $groqUrl = "https://api.groq.com/openai/v1/chat/completions";
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders(['Authorization' => "Bearer {$apiKeyGroq}"])
                    ->post($groqUrl, [
                        'model' => 'llama-3.3-70b-versatile',
                        'messages' => [['role' => 'user', 'content' => $prompt]]
                    ]);
                if ($response->successful()) {
                    $generatedText = $response->json()['choices'][0]['message']['content'] ?? '';
                }
            } catch (\Exception $e) {
                \Log::error('Groq API Error: ' . $e->getMessage());
            }
        }

        if (!$generatedText) {
            return response()->json([
                'error' => 'Gagal menghasilkan pesan AI. Silakan coba lagi atau check API key.',
                'debug' => [
                    'api_gemini_configured' => !empty($apiKeyGemini),
                    'api_groq_configured' => !empty($apiKeyGroq),
                    'type' => $type
                ]
            ], 500);
        }

        return response()->json(['message' => trim($generatedText)]);
    }
}