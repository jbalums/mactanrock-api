<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\ReceivingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveRequest;
use App\Http\Resources\ReceiveResource;
use App\Services\InventoryServices;
use App\Services\ReceivingService;

class ReceivingController extends Controller
{

    public function index(ReceivingService $receivingService)
    {
        return ReceiveResource::collection($receivingService->get());
    }
    public function store(ReceiveRequest $request, InventoryServices $inventoryServices, ReceivingService $receivingService)
    {
        $receive = $receivingService->create($request);

        if($receive->status === ReceivingStatus::Completed){
            foreach ($receive->details as $detail){
                $inventoryServices->in($detail->product_id,$detail->quantity,[
                    'receive_id' => $receive->id,
                    'expired_at' => $detail->expired_at,
                    'price' => $detail->price,
                    'user_id' =>  request()->user()->id,
                ]);
            }
        }

        return ReceiveResource::make($receive);
    }
}