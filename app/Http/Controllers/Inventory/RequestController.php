<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\RequisitionDetailsResource;
use App\Services\RequisitionServices;

class RequestController extends Controller
{

    public function index(RequisitionServices $services)
    {
        return RequisitionDetailsResource::collection($services->requestList());
    }

    public function show(RequisitionServices $services , int $id)
    {
        return RequisitionDetailsResource::make($services->showRequest($id));
    }
}