<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxProductController extends Controller
{
    /**
     * Search active products for dynamic transaction line dropdowns (PRD §12.3).
     */
    public function search(Request $request): JsonResponse
    {
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        $query = Product::with('unit')->active();

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search);
            });
        }

        $limit = min((int) $request->input('limit', 20), 50);
        $products = $query->orderBy('name')->limit($limit)->get();

        $data = $products->map(function (Product $product) use ($canViewPrice) {
            $item = [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => [
                    'name' => $product->unit->name,
                    'symbol' => $product->unit->symbol,
                    'allow_decimal' => (bool) $product->unit->allow_decimal,
                ],
                'current_stock' => (string) $product->current_stock,
                'stock_status' => $product->stock_status->value,
            ];

            if ($canViewPrice) {
                $item['purchase_price'] = (string) $product->purchase_price;
            }

            return $item;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Get real-time stock and unit for a single product (PRD §12.3).
     */
    public function stock(Product $product, Request $request): JsonResponse
    {
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        $product->load('unit');

        $data = [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'unit' => [
                'name' => $product->unit->name,
                'symbol' => $product->unit->symbol,
                'allow_decimal' => (bool) $product->unit->allow_decimal,
            ],
            'current_stock' => (string) $product->current_stock,
            'min_stock' => (string) $product->min_stock,
            'stock_status' => $product->stock_status->value,
            'is_active' => (bool) $product->is_active,
        ];

        if ($canViewPrice) {
            $data['purchase_price'] = (string) $product->purchase_price;
        }

        return response()->json(['data' => $data]);
    }
}
