<?php

namespace App\Services;

use App\Enums\RequisitionStatus;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Services\InventoryServices;

class RequisitionServices
{

    protected $inventoryServices;

    public function __construct(InventoryServices $inventoryServices)
    {
        $this->inventoryServices = $inventoryServices;
    }

    public function get()
    {
        return Issuance::query()
            ->with(['requester', 'acceptor', 'details'])
            ->where('branch_id', request()->user()->branch_id)
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name) like '%{$keyword}%' ");
                }
            )
            ->when(request('type'), fn($q, $type) => $q->where('status', $type))
            ->latest()
            ->paginate(is_integer(request()->get('paginate')) ?? 0);
    }
}
