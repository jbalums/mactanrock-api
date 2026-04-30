<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\V2\RequisitionDiscrepancyIndexRequest;
use App\Http\Resources\V2\RequisitionDiscrepancyResource;
use App\Services\V2\RequisitionDiscrepancyServices;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequisitionDiscrepancyController extends Controller
{
    public function __invoke(
        RequisitionDiscrepancyIndexRequest $request,
        RequisitionDiscrepancyServices $services
    ): AnonymousResourceCollection {
        return RequisitionDiscrepancyResource::collection(
            $services->paginateIndex($request)
        )->additional([
            'api_version' => 'v2',
        ]);
    }
}
