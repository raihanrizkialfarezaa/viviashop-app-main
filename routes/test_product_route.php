<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-product-data/{id}', function($id) {
    $product = \App\Models\Product::find($id);
    
    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }
    
    return response()->json([
        'success' => true,
        'product' => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'type' => $product->type,
            'total_stock' => $product->total_stock,
            'status' => $product->status
        ],
        'debug' => [
            'total_stock_type' => gettype($product->total_stock),
            'price_type' => gettype($product->price),
            'total_stock_value' => $product->total_stock,
            'price_value' => $product->price
        ]
    ]);
});