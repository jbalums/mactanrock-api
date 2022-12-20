<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RequisitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Requisition;

class AcceptingStatsController extends Controller
{

    public function index()
    {
        $request_orders = Requisition::query()->where('status',RequisitionStatus::Pending)->count();
        return [
            'request_orders'   => $request_orders,
            'issuance'          => 0
        ];
    }
}