<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RequisitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequisitionDetailsResource;
use App\Models\RequisitionDetail;
use Illuminate\Http\Request;

class AcceptOrderController extends Controller
{

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $requisition = RequisitionDetail::query()
            ->where('status', RequisitionStatus::Pending)
            ->where('location_id', $user->branch_id)
            ->findOrFail($id);
        $request->status = RequisitionStatus::Accepted;
        $request->save();

        return RequisitionDetailsResource::make($requisition);
    }
}