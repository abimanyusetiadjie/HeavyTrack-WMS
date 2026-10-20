@php
    $currentSearch = $this->tableSearch ?? '';
    $currentCategory = $this->filterCategory ?? '';
    $isLowStock = (bool) ($this->filterLowStock ?? false);
@endphp

<div class="product-toolbar-card">
    <!-- 1. Search Box -->
    <div class="product-search-wrapper">
        <svg class="product-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input 
            type="text" 
            wire:model.live.debounce.400ms="tableSearch" 
            placeholder="Cari nama barang, barcode, atau SKU..." 
            class="product-search-input"
        />
        @if(!empty($currentSearch))
            <button 
                type="button" 
                wire:click="$set('tableSearch', '')" 
                class="product-search-clear" 
                title="Hapus Pencarian"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 14px; height: 14px;">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        @endif
    </div>

    <!-- 2. Category Dropdown -->
    <div class="product-category-wrapper">
        <select wire:model.live="filterCategory" class="product-category-select">
            <option value="">Semua Kategori</option>
            @php
                $categoryList = method_exists($this, 'getCategories') 
                    ? $this->getCategories() 
                    : \App\Models\Category::orderBy('name')->get();
            @endphp
            @foreach($categoryList as $cat)
                <option value="{{ $cat->id }}" {{ (string)$currentCategory === (string)$cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <svg class="product-category-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </div>

    <!-- 3. Low Stock Button (< 100) -->
    <button 
        type="button" 
        wire:click="toggleLowStock" 
        class="product-low-stock-btn {{ $isLowStock ? 'is-active' : '' }}"
        title="Filter stok kurang dari 100"
    >
        <span class="warning-triangle">⚠️</span>
        <span class="warning-text">Stok &lt; 100</span>
    </button>
</div>

<style>
/* Sembunyikan search bawaan dan filters box bawaan agar tampilan persis seperti mockup */
.fi-ta-header-toolbar {
    display: none !important;
}
.fi-ta-filters-above-content-ctn {
    display: none !important;
}

/* Container utama toolbar dalam 1 baris */
.product-toolbar-card {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 12px !important;
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    padding: 10px 14px !important;
    margin-bottom: 8px !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.dark .product-toolbar-card {
    background-color: #18181b !important;
    border-color: #27272a !important;
}

/* 1. Wrapper Kolom Pencarian (Memanjang di sisi kiri) */
.product-search-wrapper {
    position: relative !important;
    flex: 1 1 0% !important;
    display: flex !important;
    align-items: center !important;
    min-width: 0 !important;
}

.product-search-icon {
    position: absolute !important;
    left: 14px !important;
    width: 18px !important;
    height: 18px !important;
    color: #94a3b8 !important;
    pointer-events: none !important;
}

.product-search-input {
    width: 100% !important;
    height: 42px !important;
    padding: 8px 36px 8px 40px !important;
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
    color: #1e293b !important;
    background-color: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    outline: none !important;
    transition: all 0.15s ease-in-out !important;
    box-sizing: border-box !important;
}

.product-search-input:focus {
    background-color: #ffffff !important;
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important;
}

.product-search-input::placeholder {
    color: #94a3b8 !important;
}

.dark .product-search-input {
    background-color: #27272a !important;
    border-color: #3f3f46 !important;
    color: #f8fafc !important;
}

.dark .product-search-input:focus {
    background-color: #18181b !important;
    border-color: #f59e0b !important;
}

.product-search-clear {
    position: absolute !important;
    right: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 20px !important;
    height: 20px !important;
    border-radius: 9999px !important;
    background-color: #e2e8f0 !important;
    color: #64748b !important;
    border: none !important;
    cursor: pointer !important;
    padding: 0 !important;
}

.product-search-clear:hover {
    background-color: #cbd5e1 !important;
    color: #0f172a !important;
}

/* 2. Dropdown Kategori */
.product-category-wrapper {
    position: relative !important;
    width: 230px !important;
    min-width: 180px !important;
    flex-shrink: 0 !important;
}

.product-category-select {
    width: 100% !important;
    height: 42px !important;
    padding: 8px 36px 8px 14px !important;
    font-size: 0.875rem !important;
    line-height: 1.25rem !important;
    font-weight: 500 !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    outline: none !important;
    cursor: pointer !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    transition: all 0.15s ease-in-out !important;
    box-sizing: border-box !important;
}

.product-category-select:focus {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important;
}

.dark .product-category-select {
    background-color: #27272a !important;
    border-color: #3f3f46 !important;
    color: #f8fafc !important;
}

.product-category-arrow {
    position: absolute !important;
    right: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 18px !important;
    height: 18px !important;
    color: #64748b !important;
    pointer-events: none !important;
}

/* 3. Tombol Warning Stok < 100 */
.product-low-stock-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    height: 42px !important;
    padding: 8px 16px !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    transition: all 0.15s ease-in-out !important;
    box-sizing: border-box !important;
}

.product-low-stock-btn:hover {
    background-color: #f8fafc !important;
    border-color: #94a3b8 !important;
}

.product-low-stock-btn.is-active {
    background-color: #fffbeb !important;
    border-color: #f59e0b !important;
    color: #b45309 !important;
    box-shadow: 0 0 0 1.5px #f59e0b !important;
}

.dark .product-low-stock-btn {
    background-color: #27272a !important;
    border-color: #3f3f46 !important;
    color: #e4e4e7 !important;
}

.dark .product-low-stock-btn:hover {
    background-color: #3f3f46 !important;
}

.dark .product-low-stock-btn.is-active {
    background-color: rgba(245, 158, 11, 0.18) !important;
    border-color: #f59e0b !important;
    color: #fbbf24 !important;
}

.warning-triangle {
    font-size: 1rem !important;
    line-height: 1 !important;
}

@media (max-width: 640px) {
    .product-toolbar-card {
        flex-wrap: wrap !important;
    }
    .product-search-wrapper {
        min-width: 100% !important;
    }
    .product-category-wrapper {
        flex: 1 1 auto !important;
        width: auto !important;
    }
    .product-low-stock-btn {
        flex: 1 1 auto !important;
    }
}
</style>
