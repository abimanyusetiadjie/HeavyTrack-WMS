<?php

use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Dapatkan atau siapkan Gudang 1: GUDANG MITRA ABADI
        $gma = Warehouse::where('code', 'GMA')
            ->orWhere('code', 'GDG-PUSAT')
            ->orWhere('id', 1)
            ->first();

        if ($gma) {
            $gma->update([
                'name' => 'GUDANG MITRA ABADI',
                'code' => 'GMA',
                'address' => 'KAWASAN INDUSTRI MITRA ABADI NUSANTARA, BLOK A NO. 12',
                'is_active' => true,
                'deleted_at' => null,
            ]);
        } else {
            $gma = Warehouse::create([
                'id' => 1,
                'name' => 'GUDANG MITRA ABADI',
                'code' => 'GMA',
                'address' => 'KAWASAN INDUSTRI MITRA ABADI NUSANTARA, BLOK A NO. 12',
                'is_active' => true,
            ]);
        }

        // 2. Dapatkan atau siapkan Gudang 2: GUDANG ANEKARASA
        $gar = Warehouse::where('code', 'GAR')
            ->orWhere('code', 'GDG-TRN')
            ->orWhere('id', 2)
            ->first();

        if ($gar && $gar->id !== $gma->id) {
            $gar->update([
                'name' => 'GUDANG ANEKARASA',
                'code' => 'GAR',
                'address' => 'GUDANG CABANG ANEKARASA',
                'is_active' => true,
                'deleted_at' => null,
            ]);
        } else {
            $gar = Warehouse::create([
                'name' => 'GUDANG ANEKARASA',
                'code' => 'GAR',
                'address' => 'GUDANG CABANG ANEKARASA',
                'is_active' => true,
            ]);
        }

        $allowedWarehouseIds = [$gma->id, $gar->id];

        // 3. Remap transaksi dan saldo dari gudang lain ke Gudang Mitra Abadi sebelum dihapus
        $otherWarehouseIds = Warehouse::withTrashed()
            ->whereNotIn('id', $allowedWarehouseIds)
            ->pluck('id')
            ->toArray();

        if (!empty($otherWarehouseIds)) {
            // Remap stock balances
            $otherBalances = DB::table('stock_balances')
                ->whereIn('warehouse_id', $otherWarehouseIds)
                ->get();

            foreach ($otherBalances as $balance) {
                $existingGmaBalance = DB::table('stock_balances')
                    ->where('warehouse_id', $gma->id)
                    ->where('part_id', $balance->part_id)
                    ->first();

                if ($existingGmaBalance) {
                    DB::table('stock_balances')
                        ->where('id', $existingGmaBalance->id)
                        ->update([
                            'qty_on_hand' => $existingGmaBalance->qty_on_hand + $balance->qty_on_hand,
                            'qty_reserved' => $existingGmaBalance->qty_reserved + $balance->qty_reserved,
                            'updated_at' => now(),
                        ]);
                    DB::table('stock_balances')->where('id', $balance->id)->delete();
                } else {
                    DB::table('stock_balances')
                        ->where('id', $balance->id)
                        ->update(['warehouse_id' => $gma->id]);
                }
            }

            // Remap stock ledgers
            DB::table('stock_ledgers')
                ->whereIn('warehouse_id', $otherWarehouseIds)
                ->update(['warehouse_id' => $gma->id]);

            // Remap delivery orders
            DB::table('delivery_orders')
                ->whereIn('warehouse_id', $otherWarehouseIds)
                ->update(['warehouse_id' => $gma->id]);

            // Remap goods receipts
            DB::table('goods_receipts')
                ->whereIn('warehouse_id', $otherWarehouseIds)
                ->update(['warehouse_id' => $gma->id]);

            // Hapus permanen gudang lain
            Warehouse::withTrashed()
                ->whereIn('id', $otherWarehouseIds)
                ->forceDelete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap dipertahankan karena 2 gudang ini permanen
    }
};
