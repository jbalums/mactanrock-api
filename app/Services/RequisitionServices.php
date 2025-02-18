<?php

namespace App\Services;

use App\Enums\RequisitionStatus;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;
use App\Http\Resources\RequisitionResource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
            ->with(['requester','acceptor', 'location'])
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name) like '%{$keyword}%' ");
                }
            )
            ->when(request('type'), fn($q, $type) => $q->where('status', $type))
            ->where('branch_id', request()->branch_id ? request()->branch_id : request()->user()->branch_id)
            ->latest()
            ->paginate(is_integer(request()->get('paginate')) ?? 0);
    }

    public function getIssuances()
    {
        return Requisition::query()
            ->with(['requester', 'acceptor'])
            ->where('issuance_status', '!=', '')
            ->where('status', 'accepted')
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name,issuance_status) like '%{$keyword}%' ");
                }
            )
            // ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(request('paginate') ?? 12);
    }
    public function getReceivingIssuances()
    {
        return Requisition::query()
            ->with(['requester', 'acceptor'])
            ->where('issuance_status', 'completed')
            ->where('status', 'accepted')
            ->where('branch_id', request()->user()->branch_id)
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name,issuance_status) like '%{$keyword}%' ");
                }
            )
            // ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(request('paginate') ?? 12);
    }

    public function getIssuancesForApproval()
    {
        return Requisition::query()
            ->with(['requester', 'acceptor'])
            ->where('status', 'pending_approval')
            ->where('issuance_status', 'completed')
            ->when(
                request('keyword'),
                function (Builder $q) {
                    $keyword = request('keyword');
                    return $q->whereRaw("CONCAT_WS(' ',project_code,account_code,purpose,status,project_name,issuance_status) like '%{$keyword}%' ");
                }
            )
            // ->when( request('type'), fn($q,$type) => $q->where('status', $type))
            ->latest()
            ->paginate(request('paginate') ?? 12);
    }

    public function requestList()
    {
        return RequisitionDetail::query()
            ->whereHas('requisition', fn($q) => $q->where('status', RequisitionStatus::Approved))
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
            ->with([
                'requisition' => [
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
        $user = request()->user();
        return Requisition::query()->with([
            'details' => [
                'location',
                'items' => [
                    'product'
                ]
            ],
            'requester'
        ])
            // ->where('branch_id', $user->branch_id)
            ->findOrFail($id);
    }
    public function create()
    {
        try {
            DB::beginTransaction();

            $user = request()->user();
            $requisition = new Requisition();
            $requisition->project_code = request()->get('project_code');
            $requisition->account_code = request()->get('account_code');
            $requisition->purpose = request()->get('purpose');
            $requisition->branch_id = $user->branch_id;
            $requisition->request_from_branch_id = request()->get('branch_id');
            $requisition->needed_at = request()->get('date_needed');
            $requisition->user_id = $user->id;
            $requisition->save();


            $products = InventoryLocation::query()
                ->whereIn('id', request('inventory_id'))
                ->where('branch_id', request()->get('purpose') == "internal_use" ? $user->branch_id : 1)
                ->get();


            $groupByLocations = $products->groupBy('branch_id')->all();

            $qtyResolver = [];

            foreach (request('inventory_id') as $key => $product) {
                $qtyResolver[$product] = request()->get('quantity')[$key] ?? 0;
            }

            foreach ($groupByLocations as $key => $data) {
                $info = new RequisitionDetail();
                $info->location_id = $key;
                $info->requisition_id = $requisition->id;
                $info->save();
                $items = [];
                foreach ($data as $item) {
                    $qty = $qtyResolver[$item->id] ?? 0;
                    if ($qty > 0) {
                        $items[] = [
                            'requisition_detail_id' => $info->id,
                            'request_quantity' => $qty,
                            'full_filled_quantity' => 0,
                            'product_id' => $item->product_id,
                            'status' => 'incomplete'
                        ];
                    }
                }
                RequisitionItem::query()->insert($items);
            }

            DB::commit();
            return RequisitionResource::make($this->show($requisition->id));
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 200);
        }
    }

    public function updateStatus(int $id, string $status, string $remarks = "")
    {
        $requisition = Requisition::query()->findOrFail($id);

        $requisition->status = $status;
        $requisition->remarks = $remarks;

        $requisition->save();

        return $requisition;
    }
    public function getPendingRequest()
    {
        return Requisition::query()
            ->where('status', 'pending')
            ->where('branch_id', request()->user()->branch_id)
            ->latest()
            ->count();
    }

    public function getPendingRequestAcceptance()
    {
        return Requisition::query()
            ->where('status', 'approved')
            // ->where('branch_id', request()->user()->branch_id)
            ->latest()
            ->count();
    }

    public function getPendingForIssuance()
    {
        return Requisition::query()
            ->where('issuance_status', 'pending')
            ->where('status', 'accepted')
            ->latest()
            ->count();
    }
    public function getPendingReceivingOrders()
    {
        return Requisition::query()
            ->where('issuance_status', 'completed')
            ->where('status', 'accepted')
            ->where('branch_id', request()->user()->branch_id)
            ->latest()
            ->count();
    }
    public function getPendingForApproval()
    {
        return Requisition::query()
            ->where('status', 'pending')
            ->where('branch_id', request()->user()->branch_id)
            ->latest()
            ->count();
    }

    public function approvedRequisition(int $id)
    {
        try {
            DB::beginTransaction();
            $user = request()->user();
            $complete_if_in_array = array('sale', 'internal_use');

            $requisition = Requisition::query()->where('status', RequisitionStatus::Pending)->findOrFail($id);
            $requisition->status = in_array($requisition->purpose, $complete_if_in_array) ? RequisitionStatus::Completed : RequisitionStatus::Approved;
            $requisition->accepted_by_id = $user->id;
            $requisition->date_approved = Carbon::now()->format('Y-m-d H:i:s');
            $inventoryData = null;
            if (in_array($requisition->purpose, $complete_if_in_array)) {
                $requisition->load('details');
                foreach ($requisition->details as $detail) {
                    $rd = RequisitionDetail::query()->findOrFail($detail->id);
                    $rd->load('items');
                    foreach ($rd->items as $item) {
                        $rd_item = RequisitionItem::query()->findOrFail($item->id);
                        $rd_item->full_filled_quantity = (int)$rd_item->request_quantity;
                        $rd_item->status = 'completed';

                        $rd_item->save();
                        if ($requisition->purpose == 'finished_goods') {

                            $data = [
                                'from_request_id' => $requisition->id,
                                'transacted_by_id' => $user->id,
                                'accepted_by_id' => $user->id,
                                'from_branch_id' => $user->branch_id,
                                'to_branch_id' => $user->branch_id,
                                'description' => 'finished goods received'
                            ];
                            $this->inventoryServices->stockIn($rd_item->product_id, (int)$item->request_quantity, $data, $user->branch_id);
                        } else {

                            $data = [
                                'from_request_id' => $requisition->id,
                                'transacted_by_id' => $user->id,
                                'accepted_by_id' => $user->id,
                                'from_branch_id' => $user->branch_id,
                                'to_branch_id' => $user->branch_id,
                                'description' => $requisition->purpose
                            ];
                            $inventoryData = $this->inventoryServices->stockOut($rd_item->product_id, (int)$item->request_quantity, $data, $user->branch_id);
                        }
                    }
                }
            }

            $requisition->save();
            DB::commit();
            return ['requisition'=>$requisition, 'inventoryData'=>$inventoryData];
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
    }
    
    public function declineRequisition(int $id)
    {
        try {
            DB::beginTransaction();
            $user = request()->user();
            $requisition = Requisition::query()->where('status', RequisitionStatus::Pending)->findOrFail($id);
            $requisition->status = 'declined';
            $requisition->declined_by_id = $user->id;
            $requisition->date_declined = Carbon::now()->format('Y-m-d H:i:s');
            $requisition->load('details');
            foreach ($requisition->details as $detail) {
                $rd = RequisitionDetail::query()->findOrFail($detail->id);
                $rd->load('items');
                foreach ($rd->items as $item) {
                    $rd_item = RequisitionItem::query()->findOrFail($item->id);
                    $rd_item->full_filled_quantity = 0;
                    $rd_item->status = 'declined';
                    $rd_item->save();
                }
            }
            $requisition->save();
            DB::commit();
            return $requisition;
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
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
        try {
            DB::beginTransaction();
            $is_complete = true;
            $user = request()->user();


            $requisition = Requisition::query()->findOrFail($id);
            $requisition->load('details');
            foreach ($requisition->details as $detail) {
                $rd = RequisitionDetail::query()->findOrFail($detail->id);
                $rd->load('items');
                foreach ($rd->items as $item) {
                    $issued_qty = request()->get('issued_qty')[$detail->id][$item->id];
                    if ($issued_qty) {
                        $rd_item = RequisitionItem::query()->findOrFail($item->id);
                        $rd_item->full_filled_quantity = $issued_qty;
                        $rd_item->status = $issued_qty == $rd_item->request_quantity ? 'completed' : 'incomplete';
                        $rd_item->save();

                        if ($requisition->purpose == 'finished_goods') {

                            // $dataIn = [
                            //     'from_request_id' => $requisition->id,
                            //     'transacted_by_id' => $user->id,
                            //     'accepted_by_id' => $user->id,
                            //     'from_branch_id' => 1,
                            //     'to_branch_id' => $requisition->branch_id,
                            //     'description' => $requisition->purpose . ' item issuance'
                            // ];
                            // $stock_in = $this->inventoryServices->stockIn($item->product_id, $issued_qty, $dataIn, 1);
                        } else {


                            $dataOut = [
                                'from_request_id' => $requisition->id,
                                'transacted_by_id' => $user->id,
                                'accepted_by_id' => $user->id,
                                'from_branch_id' => 1,
                                'to_branch_id' => $requisition->branch_id,
                                'description' => $requisition->purpose . ' item issuance'
                            ];
                            $stock_out = $this->inventoryServices->stockOut($item->product_id, $issued_qty, $dataOut);
                        }


                        if ($issued_qty != $rd_item->request_quantity) {
                            $is_complete = false;
                        }
                    }
                }
            }
            $requisition->status = 'accepted';
            $requisition->issuance_status = $is_complete ? 'completed' : 'incomplete';
            $requisition->save();
            DB::commit();
            return $requisition;
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
    }
    public function receivedIssuance(int $id)
    {
        $is_complete = true;
        $user = request()->user();

        try {
            DB::beginTransaction();
            $requisition = Requisition::query()->findOrFail($id);
            $requisition->load('details');
            foreach ($requisition->details as $detail) {
                $rd = RequisitionDetail::query()->findOrFail($detail->id);
                $rd->load('items');
                $rd->load('location');
                foreach ($rd->items as $item) {
                    $received_qty = (int) request()->get('received_qty')[$detail->id][$item->id];
                    if ($received_qty) {
                        $rd_item = RequisitionItem::query()->findOrFail($item->id);
                        $rd_item->full_filled_quantity = $received_qty;
                        $rd_item->status = $received_qty == $rd_item->request_quantity ? 'completed' : 'incomplete';
                        $rd_item->save();
                        // return $rd_item;

                        $dataIn = [
                            'transacted_by_id' => $user->id,
                            'from_request_id' => $requisition->id,
                            'accepted_by_id' => $user->id,
                            'from_branch_id' => 1,
                            'to_branch_id' => $user->branch_id,
                            'description' => $requisition->purpose . ' received issuance'
                        ];

                        $stock_in = $this->inventoryServices->stockIn($item->product_id, $received_qty, $dataIn, $user->branch_id);
                        // return $stock_in;

                        if ($received_qty != $rd_item->request_quantity) {
                            $is_complete = false;
                        }
                    }
                }
            }
            $requisition->status = $is_complete ? 'completed' : 'accepted';
            $requisition->issuance_status = $is_complete ? 'completed' : 'incomplete';
            $requisition->save();

            DB::commit();

            return $requisition;
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
    }
}
