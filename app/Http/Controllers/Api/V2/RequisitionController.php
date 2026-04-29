<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\V2\RequisitionIndexRequest;
use App\Http\Resources\V2\RequisitionResource;
use App\Services\V2\RequisitionServices;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequisitionController extends Controller
{
    public function index(RequisitionIndexRequest $request, RequisitionServices $services): AnonymousResourceCollection
    {
        return RequisitionResource::collection(
            $services->paginateIndex($request)
        )->additional([
            'api_version' => 'v2',
        ]);
    }
}
