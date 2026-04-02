<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductJsonRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly ProductJsonRepository $products
    ) {}

    public function index(): View
    {
        return view('inventory.index', [
            'initialPayload' => $this->buildPayload(),
        ]);
    }

    public function indexJson(): JsonResponse
    {
        return response()->json($this->buildPayload());
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->products->add($request->validated());

        return response()->json($this->buildPayload());
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $updated = $this->products->update($id, $request->validated());

        if ($updated === null) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json($this->buildPayload());
    }

    /**
     * @return array{items: list<array<string, mixed>>, grand_total: float}
     */
    private function buildPayload(): array
    {
        $raw = $this->products->all();
        $items = [];
        $grandTotal = 0.0;

        foreach ($raw as $row) {
            $quantity = (int) ($row['quantity'] ?? 0);
            $price = (float) ($row['price_per_item'] ?? 0);
            $totalValue = round($quantity * $price, 2);
            $grandTotal += $totalValue;

            $items[] = [
                'id' => $row['id'],
                'product_name' => $row['product_name'],
                'quantity' => $quantity,
                'price_per_item' => $price,
                'submitted_at' => $row['submitted_at'],
                'total_value' => $totalValue,
            ];
        }

        return [
            'items' => $items,
            'grand_total' => round($grandTotal, 2),
        ];
    }
}
