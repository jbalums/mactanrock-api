<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\RequisitionServices;

class ApprovingRequisitionController extends Controller
{
    public function update(RequisitionServices $requisitionServices, int $id)
    {
        return $requisitionServices->approvedRequisition($id);

        // return response()->noContent();
    }
    public function decline(RequisitionServices $requisitionServices, int $id)
    {
        return $requisitionServices->declineRequisition($id);
    }
}
