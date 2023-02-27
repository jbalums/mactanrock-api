<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\ProjectPlantService;
use App\Http\Resources\RequisitionResource;

class ProjectPlantOrdersController extends Controller
{

    public function index(ProjectPlantService $services)
    {
        return RequisitionResource::collection($services->get());
    }
    public function show(ProjectPlantService $services,  int $id)
    {
        return RequisitionResource::make($services->show($id));
    }
}