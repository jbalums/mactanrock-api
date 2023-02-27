<?php

namespace App\Services;

use App\Enums\RequisitionStatus;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;
use Carbon\Carbon;

class ProjectPlantService
{

    public function get()
    {
        return Requisition::query()
            ->with(['requester','acceptor'])
            ->where('branch_id', request()->user()->branch_id)
            ->where('purpose', 'project_plant')
            ->where('status', 'approved')
            ->when( request('keyword'),
                function(Builder $q){
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code) like '%{$keyword}%' ");
                })
            ->latest()
            ->paginate(is_integer(request('paginate',12)) ?request('paginate'):0);
    }
 

    public function show(int $id)
    {
        return Requisition::query()->with([
            'details' => [
                'location',
                'items' => [
                    'product'
                ]
            ],
            'requester'
        ])->where('branch_id', request()->user()->branch_id)->findOrFail($id);
    }
}