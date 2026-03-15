<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\InventoryServices;
use App\Services\ReceivingService;

class ReceivingCompleteController extends  Controller
{

    public function update(InventoryServices $inventoryServices, ReceivingService $receivingService, int $id)
    {
        $receive = $receivingService->markCompleted($id);
        foreach ($receive->details as $detail) {
            $inventoryServices->in($detail->product_id, $detail->quantity, [
                'receive_id' => $receive->id,
                'expired_at' => $detail->expired_at,
                'price' => $detail->price,
                'user_id' => request()->user()->id,
                'branch_id' => $receive->branch_id,
            ]);
        }

        return $receive;
    }
}
