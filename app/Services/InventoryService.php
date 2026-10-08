<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Registra un movimiento de inventario y actualiza las existencias.
     * $quantity es con signo: positivo entra, negativo sale.
     */
    public function move(Product $product, int $branchId, float $quantity, string $type, array $extra = []): ?StockMovement
    {
        if (! $product->track_stock || $product->type === 'service' || abs($quantity) < 0.0005) {
            return null;
        }

        return DB::transaction(function () use ($product, $branchId, $quantity, $type, $extra) {
            $stock = ProductStock::firstOrCreate(
                ['product_id' => $product->id, 'branch_id' => $branchId],
                ['quantity' => 0]
            );
            $stock->update(['quantity' => (float) $stock->quantity + $quantity]);

            return StockMovement::create([
                'company_id' => $product->company_id,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'user_id' => $extra['user_id'] ?? auth()->id(),
                'document_id' => $extra['document_id'] ?? null,
                'type' => $type,
                'quantity' => $quantity,
                'cost' => $extra['cost'] ?? $product->cost_price,
                'reference' => $extra['reference'] ?? null,
                'notes' => $extra['notes'] ?? null,
                'date' => $extra['date'] ?? today(),
            ]);
        });
    }

    /**
     * Fija la existencia de un producto a una cantidad concreta (ajuste).
     */
    public function setQuantity(Product $product, int $branchId, float $newQuantity, ?string $notes = null): ?StockMovement
    {
        $current = $product->stockIn($branchId);

        return $this->move($product, $branchId, $newQuantity - $current, 'adjustment', ['notes' => $notes]);
    }

    public function transfer(Product $product, int $fromBranchId, int $toBranchId, float $quantity, ?string $notes = null): void
    {
        DB::transaction(function () use ($product, $fromBranchId, $toBranchId, $quantity, $notes) {
            $reference = 'TRF-'.now()->format('ymdHis');
            $this->move($product, $fromBranchId, -$quantity, 'transfer_out', compact('notes', 'reference'));
            $this->move($product, $toBranchId, $quantity, 'transfer_in', compact('notes', 'reference'));
        });
    }

    /**
     * Revierte todos los movimientos generados por un documento.
     */
    public function revertDocument(Document $document): void
    {
        $movements = StockMovement::where('document_id', $document->id)->get();

        foreach ($movements as $movement) {
            $stock = ProductStock::where('product_id', $movement->product_id)
                ->where('branch_id', $movement->branch_id)->first();
            $stock?->update(['quantity' => (float) $stock->quantity - (float) $movement->quantity]);
            $movement->delete();
        }

        if ($document->stock_applied) {
            $document->forceFill(['stock_applied' => false])->saveQuietly();
        }
    }

    /**
     * Aplica (o reaplica) el efecto de un documento sobre el inventario.
     */
    public function syncDocument(Document $document): void
    {
        $this->revertDocument($document);

        if (! $this->shouldApply($document)) {
            return;
        }

        $direction = $document->cfg('stock');
        $type = match ($document->type) {
            'invoice', 'delivery_note' => 'sale',
            'credit_note' => 'return_in',
            'purchase_order' => 'purchase',
            'debit_note' => 'return_out',
        };

        $document->loadMissing('items.product');

        foreach ($document->items as $item) {
            if (! $item->product) {
                continue;
            }
            $this->move($item->product, $document->branch_id, $direction * (float) $item->quantity, $type, [
                'document_id' => $document->id,
                'reference' => $document->number,
                'cost' => $document->type === 'purchase_order' ? $item->unit_price : $item->product->cost_price,
                'date' => $document->date,
            ]);
        }

        $document->forceFill(['stock_applied' => true])->saveQuietly();
    }

    protected function shouldApply(Document $document): bool
    {
        if ($document->status === 'cancelled' || $document->cfg('stock') === 0 || ! $document->affects_stock) {
            return false;
        }

        return match ($document->type) {
            // Una factura generada desde un albarán ya descontó el inventario.
            'invoice' => $document->parent?->type !== 'delivery_note',
            'purchase_order' => $document->status === 'received',
            default => true,
        };
    }
}
