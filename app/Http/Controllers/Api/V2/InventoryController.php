<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\InventoryIndexRequest;
use App\Http\Resources\V2\InventoryResource;
use App\Models\InventoryLocation;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    public function index(InventoryIndexRequest $request): AnonymousResourceCollection
    {
        $query = $this->inventoryQuery($request)
            ->with(['product.category', 'branch']);

        $this->applySorting($query, $request);

        $perPage = $request->integer('per_page') ?: $request->integer('paginate') ?: 15;

        return InventoryResource::collection(
            $query->paginate($perPage)->appends($request->query())
        )->additional([
            'api_version' => 'v2',
        ]);
    }

    public function summary(InventoryIndexRequest $request): JsonResponse
    {
        $query = $this->inventoryQuery($request, false);
        $branchId = $this->resolveBranchId($request);

        return response()->json([
            'data' => [
                'branch_id' => $branchId,
                'total_items' => (clone $query)->count(),
                'total_quantity' => (int) (clone $query)->sum('inventory_locations.quantity'),
                'total_inventory_value' => round((float) (clone $query)
                    ->selectRaw('COALESCE(SUM(inventory_locations.quantity * inventory_locations.price), 0) as aggregate')
                    ->value('aggregate'), 2),
                'stock_status_counts' => [
                    'in_stock' => $this->countByStockStatus($query, 'in_stock'),
                    'low' => $this->countByStockStatus($query, 'low'),
                    'reorder' => $this->countByStockStatus($query, 'reorder'),
                    'out' => $this->countByStockStatus($query, 'out'),
                ],
            ],
            'api_version' => 'v2',
        ]);
    }

    public function show(Request $request, InventoryLocation $inventory): InventoryResource
    {
        if (!$this->canViewInventory($request, $inventory)) {
            abort(404);
        }

        return InventoryResource::make(
            $inventory->load([
                'product.category',
                'branch',
            ])
        )->additional([
            'api_version' => 'v2',
        ]);
    }

    private function inventoryQuery(InventoryIndexRequest $request, bool $includeStockStatus = true): Builder
    {
        $query = InventoryLocation::query()
            ->leftJoin('products', 'inventory_locations.product_id', '=', 'products.id')
            ->select('inventory_locations.*');

        $branchId = $this->resolveBranchId($request);

        if ($branchId !== null) {
            $query->where('inventory_locations.branch_id', $branchId);
        }

        $query
            ->when(
                $request->filled('product_id'),
                fn (Builder $query) => $query->where('inventory_locations.product_id', $request->integer('product_id'))
            )
            ->when(
                $request->filled('category_id'),
                fn (Builder $query) => $query->where('products.category_id', $request->integer('category_id'))
            )
            ->when(
                $request->filled('business_unit'),
                fn (Builder $query) => $query->where('inventory_locations.business_unit', $request->input('business_unit'))
            )
            ->when(
                $request->filled('keyword'),
                fn (Builder $query) => $this->applyKeywordFilter($query, (string) $request->input('keyword'))
            );

        if ($includeStockStatus && $request->filled('stock_status')) {
            $this->applyStockStatus($query, (string) $request->input('stock_status'));
        }

        return $query;
    }

    private function applyKeywordFilter(Builder $query, string $keyword): Builder
    {
        $term = '%' . $keyword . '%';

        return $query->where(function (Builder $query) use ($term) {
            $query
                ->where('products.name', 'like', $term)
                ->orWhere('products.code', 'like', $term)
                ->orWhere('products.brand', 'like', $term)
                ->orWhere('products.description', 'like', $term)
                ->orWhere('products.account_code', 'like', $term);
        });
    }

    private function applySorting(Builder $query, InventoryIndexRequest $request): void
    {
        $sortMap = [
            'name' => 'products.name',
            'code' => 'products.code',
            'brand' => 'products.brand',
            'quantity' => 'inventory_locations.quantity',
            'total_quantity' => 'inventory_locations.total_quantity',
            'price' => 'inventory_locations.price',
            'updated_at' => 'inventory_locations.updated_at',
        ];

        $sort = $request->input('sort') ?: $request->input('column') ?: 'name';
        $direction = $request->input('direction', 'asc');

        $query->orderBy($sortMap[$sort], $direction)
            ->orderBy('inventory_locations.id');
    }

    private function countByStockStatus(Builder $query, string $status): int
    {
        $statusQuery = clone $query;

        $this->applyStockStatus($statusQuery, $status);

        return $statusQuery->count();
    }

    private function applyStockStatus(Builder $query, string $status): void
    {
        match ($status) {
            'out' => $query->where('inventory_locations.quantity', '<=', 0),
            'reorder' => $query
                ->where('inventory_locations.quantity', '>', 0)
                ->where('inventory_locations.reorder_point', '>', 0)
                ->whereColumn('inventory_locations.quantity', '<=', 'inventory_locations.reorder_point'),
            'low' => $query
                ->where('inventory_locations.quantity', '>', 0)
                ->where('inventory_locations.stock_low_level', '>', 0)
                ->whereColumn('inventory_locations.quantity', '<=', 'inventory_locations.stock_low_level')
                ->where(function (Builder $query) {
                    $query
                        ->where('inventory_locations.reorder_point', '<=', 0)
                        ->orWhereColumn('inventory_locations.quantity', '>', 'inventory_locations.reorder_point');
                }),
            default => $query
                ->where('inventory_locations.quantity', '>', 0)
                ->where(function (Builder $query) {
                    $query
                        ->where('inventory_locations.reorder_point', '<=', 0)
                        ->orWhereColumn('inventory_locations.quantity', '>', 'inventory_locations.reorder_point');
                })
                ->where(function (Builder $query) {
                    $query
                        ->where('inventory_locations.stock_low_level', '<=', 0)
                        ->orWhereColumn('inventory_locations.quantity', '>', 'inventory_locations.stock_low_level');
                }),
        };
    }

    private function resolveBranchId(InventoryIndexRequest $request): ?int
    {
        if ($this->canViewAllBranches($request)) {
            return $request->filled('branch_id') ? $request->integer('branch_id') : null;
        }

        return (int) $request->user()->branch_id;
    }

    private function canViewInventory(Request $request, InventoryLocation $inventory): bool
    {
        return $this->canViewAllBranches($request)
            || (int) $inventory->branch_id === (int) $request->user()->branch_id;
    }

    private function canViewAllBranches(Request $request): bool
    {
        $user = $request->user();

        return $user->user_type === UserType::ADMIN->value
            && (int) $user->branch_id === 1;
    }
}
