<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use App\Models\Requisition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CorrectionController extends Controller
{
    public function correction(CorrectionRequest $request)
    {
        // product_id
        // qty
        // request_account_code // CHANGED TO ID
        // movement
        // return request()->all();


        try {
            DB::beginTransaction();

            $requisition = Requisition::query()->findOrFail($request->integer('id'));
            $inventory_location = InventoryLocation::query()
                ->where('branch_id', $requisition->branch_id)
                ->where('product_id', $request->integer('product_id'))
                ->firstOrFail();

            $inventory = Inventory::query()
                ->where('inventory_location_id', $inventory_location->id)
                ->where('product_id', $request->integer('product_id'))
                ->orderBy('id', 'DESC')
                ->firstOrFail();

            $inventory_transaction = new InventoryTransaction();
            $inventory_transaction->quantity = $request->integer('qty');
            $inventory_transaction->branch_id = $requisition->branch_id;
            $inventory_transaction->transacted_by_id = $requisition->accepted_by_id;
            $inventory_transaction->accepted_by_id = $requisition->accepted_by_id;
            $inventory_transaction->movement = $request->input('movement');
            $inventory_transaction->to_branch_id = $requisition->branch_id;
            $inventory_transaction->from_branch_id = $requisition->branch_id;
            // $inventory_transaction->receive_id = request('qty');
            $inventory_transaction->details = '';
            $inventory_transaction->action = 'auto';
            $inventory_transaction->inventory_id = $inventory->id;
            $inventory_transaction->product_id = $request->integer('product_id');
            $inventory_transaction->from_request_id = $requisition->id;
            $inventory_transaction->save();

            $delta = $request->input('movement') === 'in'
                ? $request->integer('qty')
                : -$request->integer('qty');

            $nextInventoryQuantity = (int) $inventory->quantity + $delta;
            $nextLocationQuantity = (int) $inventory_location->quantity + $delta;
            $nextLocationTotalQuantity = (int) $inventory_location->total_quantity + $delta;

            if ($nextInventoryQuantity < 0 || $nextLocationQuantity < 0 || $nextLocationTotalQuantity < 0) {
                throw ValidationException::withMessages([
                    'qty' => ['Insufficient stock quantity for this correction.'],
                ]);
            }

            $inventory->quantity = $nextInventoryQuantity;
            $inventory->save();

            $inventory_location->quantity = $nextLocationQuantity;
            $inventory_location->total_quantity = $nextLocationTotalQuantity;
            $inventory_location->save();
            DB::commit();
            return ['$requisition' => $requisition, '$inventory_location' => $inventory_location, '$inventory' => $inventory];
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => $request->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
    }
}
