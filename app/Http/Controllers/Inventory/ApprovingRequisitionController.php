<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\RequisitionServices;

class ApprovingRequisitionController extends Controller
{

    public function update(RequisitionServices $requisitionServices, int $id)
    {
        $requisitionServices->approvedRequisition($id);

        return response()->noContent();
    }
}