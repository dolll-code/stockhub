<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stockhub:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memeriksa konsistensi saldo persediaan dan integritas rantai mutasi tanpa memperbaiki otomatis (FR-SYS-01, BR-28)';

    /**
     * Execute the console command.
     */
    public function handle(ActivityLogger $logger): int
    {
        $this->info('Memulai audit rekonsiliasi integritas stok persediaan...');

        $products = Product::all();
        $productsChecked = $products->count();
        $discrepancies = [];

        foreach ($products as $product) {
            $movements = StockMovement::where('product_id', $product->id)
                ->orderBy('id', 'asc')
                ->get();

            // 1. Check sum of qty_change vs current_stock
            $sumChange = '0.000';
            $previousAfter = '0.000';
            $chainBroken = false;
            $chainErrorDetails = [];

            foreach ($movements as $idx => $m) {
                $before = (string) $m->qty_before;
                $change = (string) $m->qty_change;
                $after = (string) $m->qty_after;

                // Formula check: after = before + change
                $expectedAfter = bcadd($before, $change, 3);
                if (bccomp($after, $expectedAfter, 3) !== 0) {
                    $chainBroken = true;
                    $chainErrorDetails[] = "Movement #{$m->id}: rumus tidak cocok (before {$before} + change {$change} != after {$after})";
                }

                // Chain continuity check (for row > 0: before must match previous after)
                if ($idx > 0 && bccomp($before, $previousAfter, 3) !== 0) {
                    $chainBroken = true;
                    $chainErrorDetails[] = "Movement #{$m->id}: rantai terputus (before {$before} != prev after {$previousAfter})";
                }

                $previousAfter = $after;
                $sumChange = bcadd($sumChange, $change, 3);
            }

            $currentStock = (string) $product->current_stock;
            $balanceMismatch = bccomp($currentStock, $sumChange, 3) !== 0;

            if ($balanceMismatch || $chainBroken) {
                $diff = bcsub($currentStock, $sumChange, 3);
                $discrepancies[] = [
                    'product_id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'current_stock' => (float) $currentStock,
                    'movement_sum' => (float) $sumChange,
                    'difference' => (float) $diff,
                    'chain_broken' => $chainBroken,
                    'chain_errors' => $chainErrorDetails,
                ];
            }
        }

        $discrepanciesCount = count($discrepancies);

        // Record to stock-integrity log channel (PRD §5.14)
        if ($discrepanciesCount > 0) {
            Log::channel('stock-integrity')->warning("Rekonsiliasi stok mendeteksi {$discrepanciesCount} selisih dari {$productsChecked} barang.", [
                'discrepancies' => $discrepancies,
                'executed_at' => now()->toIso8601String(),
            ]);

            $this->warn("PERINGATAN: Ditemukan {$discrepanciesCount} ketidaksesuaian integritas saldo persediaan!");
            $this->table(
                ['ID', 'Kode Barang', 'Nama Barang', 'Stok Sistem', 'Jumlah Mutasi', 'Selisih', 'Rantai Rusak?'],
                array_map(fn ($d) => [
                    $d['product_id'],
                    $d['code'],
                    $d['name'],
                    $d['current_stock'],
                    $d['movement_sum'],
                    $d['difference'],
                    $d['chain_broken'] ? 'YA' : 'TIDAK',
                ], $discrepancies)
            );
        } else {
            Log::channel('stock-integrity')->info("Rekonsiliasi stok berhasil: seluruh {$productsChecked} barang konsisten dan rantai mutasi utuh.", [
                'products_checked' => $productsChecked,
                'executed_at' => now()->toIso8601String(),
            ]);

            $this->info("SUKSES: Seluruh {$productsChecked} barang persediaan konsisten dan rantai mutasi valid.");
        }

        // Record to ActivityLogger (append-only)
        $logger->log(
            action: 'system.reconcile',
            description: $discrepanciesCount > 0
                ? "Rekonsiliasi stok menemukan {$discrepanciesCount} selisih dari {$productsChecked} barang."
                : "Rekonsiliasi stok sukses: seluruh {$productsChecked} barang konsisten.",
            subject: null,
            actor: null,
            newValues: [
                'products_checked' => $productsChecked,
                'discrepancies_count' => $discrepanciesCount,
                'discrepancies' => $discrepancies,
            ]
        );

        return $discrepanciesCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
