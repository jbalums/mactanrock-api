<?php

namespace App\Services;

use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;

class RequisitionServices
{

    public function get()
    {
        return Requisition::query()
            ->with('requester')
            ->where('branch_id', request()->user()->branch_id)
            ->latest()
            ->paginate(is_integer(request('paginate',12)) ?request('paginate'):0);
    }

    public function requestList()
    {
        return RequisitionDetail::query()
            ->with(['requisition' => [
                'location',
                'requester'
            ]])
            ->where('location_id', request()->user()->branch_id)
            ->paginate(is_integer(request('paginate',12)) ?request('paginate'):0);
    }

    public function showRequest(int $id)
    {
        return RequisitionDetail::query()
            ->with(['requisition' => [
                'location',
                'requester',
            ],
                'items' => [
                    'product'
                ]
            ])
            ->where('location_id', request()->user()->branch_id)
            ->findOrFail($id);
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
    public function create()
    {
        $user = request()->user();
        $requisition = new Requisition();
        $requisition->project_code = request()->get('project_code');
        $requisition->branch_id = $user->branch_id;
        $requisition->needed_at = request()->get('date_needed');
        $requisition->user_id = $user->id;
        $requisition->save();


        $products = InventoryLocation::query()->whereIn('id', request('inventory_id'))->get();

        $groupByLocations = $products->groupBy('branch_id')->all();

        $qtyResolver = [];


        foreach (request('inventory_id') as $key => $product){
            $qtyResolver[$product] = request()->get('quantity')[$key] ?? 0;
        }

        foreach ($groupByLocations as $key => $data){
            $info = new RequisitionDetail();
            $info->location_id = $key;
            $info->requisition_id = $requisition->id;
            $info->save();
            $items = [];
            foreach ($data as $item) {
                $qty = $qtyResolver[$item->id] ?? 0;
                if($qty > 0){
                    $items[] = [
                        'requisition_detail_id' => $info->id,
                        'request_quantity' => $qty,
                        'full_filled_quantity' => 0,
                        'product_id' => $item->product_id,
                        'status' => 'incomplete'
                    ];

                    RequisitionItem::query()->insert($items);
                }

            }


        }




        return $requisition;
    }
}