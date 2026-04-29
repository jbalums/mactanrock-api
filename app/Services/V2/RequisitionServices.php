<?php

namespace App\Services\V2;

use App\Http\Requests\V2\RequisitionIndexRequest;
use App\Models\Requisition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RequisitionServices
{
    public function paginateIndex(RequisitionIndexRequest $request): LengthAwarePaginator
    {
        return Requisition::query()
            ->with(['requester.branch', 'acceptor.branch', 'location', 'declinedBy.branch'])
            ->when(
                $request->filled('keyword'),
                fn (Builder $query) => $this->applyKeywordFilter($query, (string) $request->input('keyword'))
            )
            ->when(
                $request->filled('type'),
                fn (Builder $query) => $query->where('status', $request->input('type'))
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $query) => $query->whereDate('created_at', '>=', $request->input('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $query) => $query->whereDate('created_at', '<=', $request->input('date_to'))
            )
            ->where('branch_id', $this->resolveBranchId($request))
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

    private function resolveBranchId(RequisitionIndexRequest $request): int
    {
        return $request->integer('branch_id') ?: (int) $request->user()->branch_id;
    }
}
