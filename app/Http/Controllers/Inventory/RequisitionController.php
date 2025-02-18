<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisitionRequest;
use App\Http\Resources\RequisitionResource;
use App\Services\RequisitionServices;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;

class RequisitionController extends Controller
{
    public function index(RequisitionServices $services)
    {
        return RequisitionResource::collection($services->get());
    }
    public function store(RequisitionServices $services, RequisitionRequest $request)
    {
        return $services->create();
        // return RequisitionResource::make($services->create());
    }

    public function show(RequisitionServices $services, int $id)
    {
        return RequisitionResource::make($services->show($id));
    }
    public function accept(RequisitionServices $requisitionServices, int $id)
    {
        $requisitionServices->updateStatus($id, 'accepted', '');
        $requisitionServices->updateIssuanceStatus($id, 'pending', '');

        return response()->noContent();
    }

    public function issuances(RequisitionServices $services)
    {
        return RequisitionResource::collection($services->getIssuances());
    }

    public function receivingIssuances(RequisitionServices $services)
    {
        return RequisitionResource::collection($services->getReceivingIssuances());
    }

    public function issuancesForApproval(RequisitionServices $services)
    {
        return RequisitionResource::collection($services->getIssuancesForApproval());
    }

    public function createIssuances(RequisitionServices $services, int $id)
    {
        return $services->saveIssuance($id);
    }

    public function receivedIssuances(RequisitionServices $services, int $id)
    {
        return $services->receivedIssuance($id);
    }

    public function approveIssuance(RequisitionServices $requisitionServices, int $id)
    {
        $requisitionServices->updateIssuanceStatus($id, 'approved', '');

        return response()->noContent();
    }

    public function getNotifications(RequisitionServices $services)
    {
        $pendingRequestOrder = $services->getPendingRequest();
        $pendingForApproval = $services->getPendingForApproval();
        $pendingRequestAcceptance = $services->getPendingRequestAcceptance();
        $pendingForIssuance = $services->getPendingForIssuance();
        $pendingForReceivingOrder = $services->getPendingReceivingOrders();

        $total_requests = $pendingRequestOrder + $pendingForApproval + $pendingRequestAcceptance;

        return response()->json([
            'pending_for_requests' => $pendingRequestOrder,
            'pending_for_approval' => $pendingForApproval,
            'pending_for_acceptance' => $pendingRequestAcceptance,
            'total_requests' => $total_requests,
            'pending_for_issuance' => $pendingForIssuance,
            'pending_for_receiving' => $pendingForReceivingOrder,
        ], 200);
    }
    public function deleteRequest(int $id){
        $requisition = Requisition::findOrFail($id);
        if($requisition->status == 'pending'){
            $requisitionDetailsIds = RequisitionDetail::query()->where('requisition_id', $requisition->id)->pluck('id');
            RequisitionItem::query()->whereIn('requisition_detail_id', $requisitionDetailsIds)->delete();
            RequisitionDetail::query()->where('requisition_id', $requisition->id)->delete();
            return $requisition->delete();
        }
        if($requisition->status == 'completed' && $requisition->purpose == 'sale'){
            $inventoryTransactions = InventoryTransaction::query()->where('from_request_id', $id);
            foreach ($inventoryTransactions->get() as $inventoryTransaction) {
                if($inventoryTransaction->movement == 'out'){
                    $inventoryTransaction_data = InventoryTransaction::findOrFail($inventoryTransaction->id);
                    $inventoryTransaction_data->movement = 'in';
                    $inventoryTransaction_data->details = 'refund from the cancelled request Ref#'.$inventoryTransaction->request->ref;
                    $inventoryTransaction_data->save();
                    $inventoryLocation = InventoryLocation::query()
                                            ->where('product_id', $inventoryTransaction->product_id)
                                            ->where('branch_id', $inventoryTransaction->branch_id)->first();
                    $inventoryLocation->quantity = $inventoryLocation->quantity + $inventoryTransaction->quantity;
                    $inventoryLocation->total_quantity = $inventoryLocation->total_quantity + $inventoryTransaction->quantity;
                    $inventoryLocation->save();
                }
            }
        }
    }

}
