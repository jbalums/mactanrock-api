<?php

namespace App\Services;

use App\Enums\RequisitionStatus;
use App\Models\Inventory;
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
        return Requisition::query()
            ->with(['requester','acceptor'])
            ->where('branch_id', request()->user()->branch_id)
            ->when( request('keyword'),
                function(Builder $q){
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name) like '%{$keyword}%' ");
                })
            ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(is_integer(request()->get('paginate')) ?? 0);
    }

    public function getIssuances()
    {
        return Requisition::query()
            ->with(['requester','acceptor'])
            ->where('issuance_status', '!=', '')
            ->where('status',  'accepted')
            ->when( request('keyword'),
                function(Builder $q){
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name,issuance_status) like '%{$keyword}%' ");
                })
            // ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(request('paginate') ?? 12);
    }
    public function getReceivingIssuances()
    {
        return Requisition::query()
            ->with(['requester','acceptor'])
            ->where('issuance_status', 'completed')
            ->where('status',  'accepted')
            ->where('branch_id', request()->user()->branch_id)
            ->when( request('keyword'),
                function(Builder $q){
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name,issuance_status) like '%{$keyword}%' ");
                })
            // ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(request('paginate') ?? 12);
    }
    
    public function getIssuancesForApproval()
    {
        return Requisition::query()
            ->with(['requester','acceptor'])
            ->where('status', 'pending_approval')
            ->where('issuance_status', 'completed')
            ->when( request('keyword'),
                function(Builder $q){
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name,issuance_status) like '%{$keyword}%' ");
                })
            // ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(request('paginate') ?? 12);
    }

    public function requestList()
    {
        return RequisitionDetail::query()
            ->whereHas('requisition' , fn($q) => $q->where('status', RequisitionStatus::Approved))
            ->with(['requisition' => [
                'location',
                'requester',
                'acceptor'
            ]])
            // ->where('location_id', request()->user()->branch_id)
            ->latest()
            ->paginate(request('paginate') ?? 12);
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
            // ->where('location_id', request()->user()->branch_id)
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
        ])
        // ->where('branch_id', request()->user()->branch_id)
        ->findOrFail($id);
    }
    public function create()
    {
        $user = request()->user();
        $requisition = new Requisition();
        $requisition->project_code = request()->get('project_code');
        $requisition->account_code = request()->get('account_code');
        $requisition->purpose = request()->get('purpose');
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




        return $this->show($requisition->id);
    }

    public function updateStatus(int $id, string $status, string $remarks = "")
    {
        $requisition = Requisition::query()->findOrFail($id);

        $requisition->status = $status;
        $requisition->remarks = $remarks;

        $requisition->save();

        return $requisition;
    }


    public function approvedRequisition(int $id): void
    { 
        $complete_if_in_array = array('sale', 'internal_use');

        $requisition = Requisition::query()->where('status', RequisitionStatus::Pending)->findOrFail($id);
        $requisition->status = in_array($requisition->purpose, $complete_if_in_array) ? RequisitionStatus::Completed:RequisitionStatus::Approved;
        $requisition->accepted_by_id = request()->user()->id;
        $requisition->date_approved = Carbon::now()->format('Y-m-d H:i:s');
       
        if(in_array($requisition->purpose, $complete_if_in_array)){
            $requisition->load('details');
            foreach ($requisition->details as $detail){
            $rd = RequisitionDetail::query()->findOrFail($detail->id);
                $rd->load('items'); 
                foreach($rd->items as $item){
                    $this->inventoryServices->out($item->product_id,$item->request_quantity,[ 
                        'user_id' =>  request()->user()->id,
                        'branch_id' => request()->user()->branch_id
                    ]);
                }
            }
        }

        $requisition->save();
    } 
    public function updateIssuanceStatus(int $id, string $issuance_status, string $remarks = "")
    {
        $requisition = Requisition::query()->findOrFail($id);

        $requisition->issuance_status = $issuance_status;
        $requisition->remarks = $remarks;

        $requisition->save();

        return $requisition;
    }
    public function saveIssuance(int $id)
    {   
        $is_complete = true; 

        $requisition = Requisition::query()->findOrFail($id);
        $requisition->load('details');
        foreach ($requisition->details as $detail){
        $rd = RequisitionDetail::query()->findOrFail($detail->id);
            $rd->load('items'); 
            foreach($rd->items as $item){
                $issued_qty = request()->get('issued_qty')[$detail->id][$item->id];
                if($issued_qty){
                    $rd_item = RequisitionItem::query()->findOrFail($item->id);
                    $rd_item->full_filled_quantity = $issued_qty;
                    $rd_item->status = $issued_qty == $rd_item->request_quantity ? 'completed':'incomplete';
                    $rd_item->save();

                 /*    $source_inventory_location = InventoryLocation::query()
                                        ->where('product_id', $item->product_id)
                                        ->where('branch_id', request()->user()->branch_id)
                                        ->first();
                    $source_inventory_location->total_quantity = $source_inventory_location->total_quantity - $received_qty;
                    $source_inventory_location->quantity = $source_inventory_location->quantity - $received_qty;
                    $source_inventory_location->save(); */
                    
                    
                /*     $this->inventoryServices->transaction($source_inventory->id, [
                        'quantity'=>$received_qty,
                        'branch_id'=>request()->user()->branch_id,
                        'transacted_by_id'=>request()->user()->id,
                        'accepted_by_id'=>request()->user()->id,
                        'action'=>'auto',
                        'movement'=>'out',
                    ]); */

                    /* $local_inventory_location = InventoryLocation::query()->firstOrCreate([
                        'product_id' => $item->product_id,
                        'branch_id' => $requisition->branch_id, 
                    ]);
                                        
                    $local_inventory_location->total_quantity = $local_inventory_location->total_quantity + $received_qty;
                    $local_inventory_location->quantity = $local_inventory_location->quantity + $received_qty;
                    
                    $local_inventory_location->save(); 
                     */
                /*     $this->inventoryServices->transaction($local_inventory_location->id, [
                        'quantity'=>$received_qty,
                        'branch_id'=>$requisition->branch_id,
                        'to_branch_id'=>$requisition->branch_id, 
                        'transacted_by_id'=>request()->user()->id,
                        'accepted_by_id'=>request()->user()->id, 
                        'action'=>'auto',
                        'movement'=>'in',
                    ]); */


                    if( $issued_qty != $rd_item->request_quantity){
                        $is_complete = false;
                    }
                }
            }
        }
        $requisition->status = 'accepted';
        $requisition->issuance_status = $is_complete ? 'completed' : 'incomplete';
        $requisition->save();
        return $requisition;
    }
    public function receivedIssuance(int $id)
    {   
        $is_complete = true; 

        $requisition = Requisition::query()->findOrFail($id);
        $requisition->load('details');
        foreach ($requisition->details as $detail){
        $rd = RequisitionDetail::query()->findOrFail($detail->id);
            $rd->load('items'); 
            $rd->load('location'); 
            foreach($rd->items as $item){
                $received_qty = request()->get('received_qty')[$detail->id][$item->id];
                if($received_qty){
                    $rd_item = RequisitionItem::query()->findOrFail($item->id);
                    $rd_item->full_filled_quantity = $received_qty;
                    $rd_item->status = $received_qty == $rd_item->request_quantity ? 'completed':'incomplete';
                    $rd_item->save();

                  $source_inventory_location = InventoryLocation::query()
                                        ->where('product_id', $item->product_id)
                                        ->where('branch_id', 1)
                                        ->first();
                    $source_inventory_location->total_quantity = $source_inventory_location->total_quantity - $received_qty;
                    $source_inventory_location->quantity = $source_inventory_location->quantity - $received_qty;
                    $source_inventory_location->save(); 
                    
                    /* 
                 $this->inventoryServices->transaction($source_inventory->id, [
                        'quantity'=>$received_qty,
                        'branch_id'=> $rd->location->branch_id,
                        'transacted_by_id'=>request()->user()->id,
                        'accepted_by_id'=>request()->user()->id,
                        'action'=>'auto',
                        'movement'=>'out',
                    ]);  */

                     $local_inventory_location = InventoryLocation::query()->firstOrCreate([
                        'product_id' => $item->product_id,
                        'branch_id' => $requisition->branch_id, 
                    ]);
                                        
                    $local_inventory_location->total_quantity = $local_inventory_location->total_quantity + $received_qty;
                    $local_inventory_location->quantity = $local_inventory_location->quantity + $received_qty;
                    
                    $local_inventory_location->save(); 
                   
                /*   $this->inventoryServices->transaction($local_inventory_location->id, [
                        'quantity'=>$received_qty,
                        'branch_id'=>$requisition->branch_id,
                        'to_branch_id'=>$requisition->branch_id, 
                        'transacted_by_id'=>request()->user()->id,
                        'accepted_by_id'=>request()->user()->id, 
                        'action'=>'auto',
                        'movement'=>'in',
                    ]); 
 */

                    if( $received_qty != $rd_item->request_quantity){
                        $is_complete = false;
                    }
                }
            }
        }
        $requisition->status = $is_complete ? 'completed' : 'accepted';
        $requisition->issuance_status = $is_complete ? 'completed' : 'incomplete';
        $requisition->save();
        return $requisition;
    }
    
}