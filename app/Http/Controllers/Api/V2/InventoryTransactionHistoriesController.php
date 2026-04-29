<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\V2\InventoryTransactionHistoriesRequest;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryTransaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryTransactionHistoriesController extends Controller
{
    public function __invoke(InventoryTransactionHistoriesRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('paginate') ?: 10;
        $page = $request->integer('page') ?: 1;

        $query = InventoryTransaction::query()
            ->with(['receive', 'request', 'inventory'])
            ->where('product_id', $request->integer('product_id'))
            ->where('branch_id', $request->user()->branch_id);

        if ($request->filled('keyword')) {
            $this->applyKeywordFilter($query, (string) $request->input('keyword'));
        }

        $this->applySorting($query, $request);

        $priorBalance = $this->quantityBalanceBeforePage($query, ($page - 1) * $perPage);

        $histories = $query->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        $quantityBalance = $priorBalance;
        $histories->getCollection()->transform(function (InventoryTransaction $history) use (&$quantityBalance) {
            $quantityBalance += $history->movement === 'in'
                ? (int) $history->quantity
                : -(int) $history->quantity;

            $history->quantity_balance = $quantityBalance;

            return $history;
        });

        return InventoryTransactionResource::collection($histories)->additional([
            'api_version' => 'v2',
        ]);
    }

    private function quantityBalanceBeforePage(Builder $query, int $offset): int
    {
        if ($offset <= 0) {
            return 0;
        }

        return (clone $query)
            ->limit($offset)
            ->get(['quantity', 'movement'])
            ->reduce(function (int $balance, InventoryTransaction $history) {
                return $balance + ($history->movement === 'in'
                    ? (int) $history->quantity
                    : -(int) $history->quantity);
            }, 0);
    }

    private function applySorting(Builder $query, InventoryTransactionHistoriesRequest $request): void
    {
        $sortMap = [
            'id' => 'inventory_transactions.id',
            'created_at' => 'inventory_transactions.created_at',
            'updated_at' => 'inventory_transactions.updated_at',
            'quantity' => 'inventory_transactions.quantity',
            'movement' => 'inventory_transactions.movement',
            'action' => 'inventory_transactions.action',
        ];

        $sortColumn = $request->input('sort_by') ?: $request->input('sort') ?: 'created_at';
        $sortDirection = $request->input('sort_direction', 'asc');

        $query->orderBy($sortMap[$sortColumn], $sortDirection)
            ->orderBy('inventory_transactions.id', $sortDirection);
    }

    private function applyKeywordFilter(Builder $query, string $keyword): void
    {
        $term = '%' . $keyword . '%';

        $query->where(function (Builder $query) use ($term) {
            $query
                ->where('inventory_transactions.details', 'like', $term)
                ->orWhere('inventory_transactions.action', 'like', $term)
                ->orWhere('inventory_transactions.movement', 'like', $term)
                ->orWhereHas('request', function (Builder $requestQuery) use ($term) {
                    $requestQuery
                        ->where('project_code', 'like', $term)
                        ->orWhere('account_code', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhere('project_name', 'like', $term);
                })
                ->orWhereHas('receive', function (Builder $receiveQuery) use ($term) {
                    $receiveQuery
                        ->where('purchase_order', 'like', $term)
                        ->orWhere('reference_invoice_number', 'like', $term);
                });
        });
    }
}
