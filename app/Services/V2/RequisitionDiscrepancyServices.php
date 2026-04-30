<?php

namespace App\Services\V2;

use App\Enums\UserType;
use App\Http\Requests\V2\RequisitionDiscrepancyIndexRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RequisitionDiscrepancyServices
{
    public function paginateIndex(RequisitionDiscrepancyIndexRequest $request): LengthAwarePaginator
    {
        $branchId = $this->resolveBranchId($request);
        $perPage = $request->integer('paginate') ?: 15;
        $issuedOutTotals = $this->issuedOutTransactionTotalsSubquery();

        return DB::table('requisition_items')
            ->join('requisition_details', 'requisition_items.requisition_detail_id', '=', 'requisition_details.id')
            ->join('requisitions', 'requisition_details.requisition_id', '=', 'requisitions.id')
            ->join('products', 'requisition_items.product_id', '=', 'products.id')
            ->leftJoin('inventory_locations as source_inventory_locations', function ($join) {
                $join->on('source_inventory_locations.product_id', '=', 'requisition_items.product_id')
                    ->on('source_inventory_locations.branch_id', '=', 'requisition_details.location_id');
            })
            ->leftJoinSub($issuedOutTotals, 'source_transaction_totals', function ($join) {
                $join->on('source_transaction_totals.from_request_id', '=', 'requisition_details.requisition_id')
                    ->on('source_transaction_totals.product_id', '=', 'requisition_items.product_id')
                    ->on('source_transaction_totals.branch_id', '=', 'requisition_details.location_id');
            })
            ->select([
                'requisition_items.id as requisition_item_id',
                'requisition_items.requisition_detail_id',
                'requisition_items.product_id',
                'requisition_items.request_quantity',
                'requisition_items.full_filled_quantity',
                'requisition_items.status as requisition_item_status',
                'requisition_items.used_qty',
                'requisition_items.returned_qty',
                'requisition_details.requisition_id',
                'requisition_details.location_id as source_branch_id',
                'requisitions.project_code',
                'requisitions.project_name',
                'requisitions.account_code',
                'requisitions.branch_id as destination_branch_id',
                'requisitions.status as requisition_status',
                'requisitions.issuance_status',
                'requisitions.purpose',
                'requisitions.created_at as requisition_created_at',
                'products.name as product_name',
                'products.code as product_code',
                'products.description as product_description',
                'source_inventory_locations.id as source_inventory_location_id',
                'source_inventory_locations.quantity as source_inventory_quantity',
                'source_inventory_locations.total_quantity as source_inventory_total_quantity',
                'source_inventory_locations.price as source_inventory_price',
            ])
            ->selectRaw('COALESCE(source_transaction_totals.issued_out_quantity, 0) as issued_out_quantity')
            ->when(
                $request->filled('keyword'),
                fn(Builder $query) => $this->applyKeywordFilter($query, (string) $request->input('keyword'))
            )
            ->when(
                $request->filled('type'),
                fn(Builder $query) => $query->where('requisitions.status', $request->input('type'))
            )
            ->when(
                $request->filled('date_from'),
                fn(Builder $query) => $query->whereDate('requisitions.created_at', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn(Builder $query) => $query->whereDate('requisitions.created_at', '<=', $request->input('date_to'))
            )
            ->when(
                $branchId !== null,
                fn(Builder $query) => $query->where('requisitions.branch_id', $branchId)
            )
            ->where(function (Builder $query) {
                $query
                    ->whereNull('source_inventory_locations.id')
                    ->orWhereColumn('requisition_items.full_filled_quantity', '>', 'requisition_items.request_quantity')
                    ->orWhere(function (Builder $query) {
                        $query
                            ->where('requisition_items.full_filled_quantity', '>', 0)
                            ->whereRaw('COALESCE(source_transaction_totals.issued_out_quantity, 0) <> requisition_items.request_quantity');
                    })
                    ->orWhere(function (Builder $query) {
                        $query->where(function (Builder $query) {
                            $query->where('requisitions.status', 'completed')
                                ->orWhere('requisitions.issuance_status', 'completed');
                        })->whereColumn('requisition_items.full_filled_quantity', '<>', 'requisition_items.request_quantity');
                    });
            })
            ->orderByDesc('requisitions.created_at')
            ->orderByDesc('requisition_items.id')
            ->paginate($perPage)
            ->appends($request->query());
    }

    private function applyKeywordFilter(Builder $query, string $keyword): Builder
    {
        $term = '%' . $keyword . '%';

        return $query->where(function (Builder $query) use ($term) {
            $query
                ->where('requisitions.project_code', 'like', $term)
                ->orWhere('requisitions.account_code', 'like', $term)
                ->orWhere('requisitions.purpose', 'like', $term)
                ->orWhere('requisitions.status', 'like', $term)
                ->orWhere('requisitions.project_name', 'like', $term)
                ->orWhere('products.name', 'like', $term)
                ->orWhere('products.code', 'like', $term);
        });
    }

    private function resolveBranchId(RequisitionDiscrepancyIndexRequest $request): ?int
    {
        if ($request->filled('branch_id')) {
            return $request->integer('branch_id');
        }

        if ($this->canViewApprovedAcrossBranches($request)) {
            return null;
        }

        return (int) $request->user()->branch_id;
    }

    private function canViewApprovedAcrossBranches(RequisitionDiscrepancyIndexRequest $request): bool
    {
        $user = $request->user();

        if ($request->input('type') !== 'approved') {
            return false;
        }

        if ((int) $user->branch_id !== 1) {
            return false;
        }

        return in_array($user->user_type, [
            UserType::ADMIN->value,
            UserType::WAREHOUSE_MAN->value,
            UserType::AREA_MANAGER->value,
            UserType::APPROVING_MANAGER->value,
        ], true);
    }

    private function issuedOutTransactionTotalsSubquery(): Builder
    {
        return DB::table('inventory_transactions')
            ->select([
                'from_request_id',
                'product_id',
                'branch_id',
            ])
            ->where('movement', 'out')
            ->selectRaw('SUM(quantity) as issued_out_quantity')
            ->groupBy('from_request_id', 'product_id', 'branch_id');
    }
}
