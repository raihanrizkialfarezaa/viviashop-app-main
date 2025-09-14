<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;

class StockService
{
    public static function recordMovement($variantId, $movementType, $quantity, $referenceType, $referenceId, $reason, $notes = null)
    {
        return DB::transaction(function () use ($variantId, $movementType, $quantity, $referenceType, $referenceId, $reason, $notes) {
            $variant = ProductVariant::findOrFail($variantId);
            $oldStock = $variant->stock;
            
            if ($movementType === StockMovement::MOVEMENT_IN) {
                $newStock = $oldStock + $quantity;
            } else {
                $newStock = max(0, $oldStock - $quantity);
            }
            
            $variant->update(['stock' => $newStock]);
            
            $variant->product->updateBasePrice();
            
            return StockMovement::create([
                'variant_id' => $variantId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'notes' => $notes
            ]);
        });
    }

    public static function recordSimpleProductMovement($productId, $movementType, $quantity, $referenceType, $referenceId, $reason, $notes = null)
    {
        return DB::transaction(function () use ($productId, $movementType, $quantity, $referenceType, $referenceId, $reason, $notes) {
            $product = Product::with('productInventory')->findOrFail($productId);
            
            if (!$product->productInventory) {
                ProductInventory::create([
                    'product_id' => $productId,
                    'qty' => 0
                ]);
                $product->refresh();
            }
            
            $oldStock = $product->productInventory->qty;
            
            if ($movementType === StockMovement::MOVEMENT_IN) {
                $newStock = $oldStock + $quantity;
            } else {
                $newStock = max(0, $oldStock - $quantity);
            }
            
            $product->productInventory->update(['qty' => $newStock]);
            
            $variant = $product->productVariants()->first();
            if ($variant) {
                $variant->update(['stock' => $newStock]);
                return self::recordMovement($variant->id, $movementType, $quantity, $referenceType, $referenceId, $reason, $notes);
            }
            
            return null;
        });
    }

    public static function processPurchaseStockUpdate($pembelian)
    {
        $movements = [];
        
        foreach ($pembelian->details as $detail) {
            if ($detail->variant_id) {
                $movement = self::recordMovement(
                    $detail->variant_id,
                    StockMovement::MOVEMENT_IN,
                    $detail->jumlah,
                    'purchase',
                    $pembelian->id,
                    StockMovement::REASON_PURCHASE_CONFIRMED,
                    "Purchase from supplier: {$pembelian->supplier->nama}"
                );
            } else {
                $movement = self::recordSimpleProductMovement(
                    $detail->id_produk,
                    StockMovement::MOVEMENT_IN,
                    $detail->jumlah,
                    'purchase',
                    $pembelian->id,
                    StockMovement::REASON_PURCHASE_CONFIRMED,
                    "Purchase from supplier: {$pembelian->supplier->nama}"
                );
            }
            
            if ($movement) {
                $movements[] = $movement;
            }
        }
        
        return $movements;
    }

    public static function reversePurchaseStockUpdate($pembelian)
    {
        $movements = [];
        
        foreach ($pembelian->details as $detail) {
            if ($detail->variant_id) {
                $movement = self::recordMovement(
                    $detail->variant_id,
                    StockMovement::MOVEMENT_OUT,
                    $detail->jumlah,
                    'purchase',
                    $pembelian->id,
                    StockMovement::REASON_PURCHASE_CANCELLED,
                    "Purchase cancelled from supplier: {$pembelian->supplier->nama}"
                );
            } else {
                $movement = self::recordSimpleProductMovement(
                    $detail->id_produk,
                    StockMovement::MOVEMENT_OUT,
                    $detail->jumlah,
                    'purchase',
                    $pembelian->id,
                    StockMovement::REASON_PURCHASE_CANCELLED,
                    "Purchase cancelled from supplier: {$pembelian->supplier->nama}"
                );
            }
            
            if ($movement) {
                $movements[] = $movement;
            }
        }
        
        return $movements;
    }
}