<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumeItemsRequest;
use App\Http\Requests\ReturnItemsRequest;
use App\Services\ProjectPlantService;
use App\Http\Resources\RequisitionResource;
use App\Models\RequisitionItem;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryServices;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProjectPlantOrdersController extends Controller
{
    public function index(ProjectPlantService $services)
    {
        return RequisitionResource::collection($services->get());
    }
    public function show(ProjectPlantService $services, int $id)
    {
        return RequisitionResource::make($services->show($id));
    }
    public function consumeItems(ConsumeItemsRequest $request, InventoryServices $inventory_services)
    {
        try {
            DB::beginTransaction();
            $user = $request->user();
            $data = [
                'transacted_by_id' => $user->id,
                'accepted_by_id' => $user->id,
                'from_branch_id' => $user->branch_id,
                'to_branch_id' => $user->branch_id,
                'description' => 'used/consumed items'
            ];

            $product_ids = $request->input('product_id', []);
            $qtys = $request->input('qty', []);
            $requisition_item_ids = $request->input('requisition_items_id', []);
            $stock_outs = [];
            $rq_items = [];

            foreach ($product_ids as $key => $id) {
                $amt = (int) $qtys[$key];
                if ($amt > 0) {
                    $stockOut = $inventory_services->stockOut((int) $id, $amt, $data, $user->branch_id);
                    $this->ensureInventoryOperationSucceeded($stockOut, 'qty');
                    $stock_outs[] = $stockOut;
                    $request_item = RequisitionItem::query()->findOrFail($requisition_item_ids[$key]);
                    $request_item->used_qty = (int) $request_item->used_qty + $amt;
                    $rq_items[] = $request_item->save();
                }
            }

            DB::commit();
            return response(['$product_ids' => $product_ids, 'stock_outs' => $stock_outs, 'items' => $rq_items], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => $request->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
    }

    public function returnItems(ReturnItemsRequest $request, InventoryServices $inventory_services)
    {
        try {
            DB::beginTransaction();
            $user = $request->user();
            $dataOut = [
                'transacted_by_id' => $user->id,
                'accepted_by_id' => $user->id,
                'from_branch_id' => $user->branch_id,
                'to_branch_id' => $user->branch_id,
                'description' => 'return materials to main warehouse'
            ];
            $dataIn = [
                'transacted_by_id' => $user->id,
                'accepted_by_id' => $user->id,
                'from_branch_id' => $user->branch_id,
                'returned_by_user_id' => $user->id,
                'returned_by_branch_id' => $user->branch_id,
                'to_branch_id' => 1,
                'description' => 'materials returned by warehouse'
            ];

            $product_ids = $request->input('product_id', []);
            $qtys = $request->input('qty', []);
            $requisition_item_ids = $request->input('requisition_items_id', []);
            $stock_outs = [];
            $stock_ins = [];
            $rq_items = [];

            foreach ($product_ids as $key => $id) {
                $amt = (int) $qtys[$key];
                if ($amt > 0) {
                    $stockOut = $inventory_services->stockOut((int) $id, $amt, $dataOut, $user->branch_id);
                    $this->ensureInventoryOperationSucceeded($stockOut, 'qty');
                    $stock_outs[] = $stockOut;

                    $stockIn = $inventory_services->stockIn((int) $id, $amt, $dataIn, 1);
                    $this->ensureInventoryOperationSucceeded($stockIn, 'qty');
                    $stock_ins[] = $stockIn;

                    $request_item = RequisitionItem::query()->findOrFail($requisition_item_ids[$key]);
                    $request_item->returned_qty = (int) $request_item->returned_qty + $amt;
                    $rq_items[] = $request_item->save();
                }
            }

            DB::commit();
            return response(['stock_outs' => $stock_outs, 'stock_ins' => $stock_ins, 'items' => $rq_items], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => $request->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 500);
        }
    }

    private function ensureInventoryOperationSucceeded(mixed $result, string $field): void
    {
        if (is_string($result)) {
            throw ValidationException::withMessages([
                $field => [$result],
            ]);
        }
    }
}
