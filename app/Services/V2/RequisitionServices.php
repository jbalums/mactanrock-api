<?php

namespace App\Services\V2;

use App\Enums\UserType;
use App\Http\Requests\V2\RequisitionIndexRequest;
use App\Models\Requisition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RequisitionServices
{
    public function paginateIndex(RequisitionIndexRequest $request): LengthAwarePaginator
    {
        return Requisition::query()
            ->with(['requester.branch', 'acceptor.branch', 'location', 'declinedBy.branch', 'transactions', 'details'])
            // ->withExists('transactions as has_inventory_transactions')
            ->when(
                $request->filled('keyword'),
                fn(Builder $query) => $this->applyKeywordFilter($query, (string) $request->input('keyword'))
            )
            ->when(
                $request->filled('type'),
                fn(Builder $query) => $query->where('status', $request->input('type'))
            )
            ->when(
                $request->filled('date_from'),
                fn(Builder $query) => $query->whereDate('created_at', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn(Builder $query) => $query->whereDate('created_at', '<=', $request->input('date_to'))
            )
            ->when(
                ($branchId = $this->resolveBranchId($request)) !== null,
                fn(Builder $query) => $query->where('branch_id', $branchId)
            )
            ->latest()
            ->paginate($request->integer('paginate') ?: 15)
            ->appends($request->query());
    }

    private function applyKeywordFilter(Builder $query, string $keyword): Builder
    {
        $term = '%' . $keyword . '%';

        return $query->where(function (Builder $query) use ($term) {
            $query
                ->where('project_code', 'like', $term)
                ->orWhere('account_code', 'like', $term)
                ->orWhere('purpose', 'like', $term)
                ->orWhere('status', 'like', $term)
                ->orWhere('project_name', 'like', $term);
        });
    }

    private function resolveBranchId(RequisitionIndexRequest $request): ?int
    {
        if ($request->filled('branch_id')) {
            return $request->integer('branch_id');
        }

        if ($this->canViewApprovedAcrossBranches($request)) {
            return null;
        }

        return (int) $request->user()->branch_id;
    }

    private function canViewApprovedAcrossBranches(RequisitionIndexRequest $request): bool
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
}
