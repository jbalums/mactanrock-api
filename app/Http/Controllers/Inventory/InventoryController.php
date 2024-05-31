<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Resources\InventoryLocationResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\InventoryTransactionResource;
use App\Http\Resources\ProductLocationResource;
use App\Models\InventoryTransaction;
use App\Models\InventoryLocation;
use App\Models\Inventory;
use App\Models\User;
use App\Models\Requisition;

use App\Services\InventoryServices;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InventoryController
{
    public function index(InventoryServices $services)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['name', 'description', 'quantity', 'code', 'brand'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])]
        ]);
        return InventoryLocationResource::collection($services->getList());
    }
    public function itemCosting(InventoryServices $services)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['name', 'description', 'quantity', 'code', 'brand'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])]
        ]);
        $items = $services->getItemCosting();
        return ProductLocationResource::collection($items->load([
            'product', 'product.category', 'branch', 'location', 'inventory'
        ]));
        // return ProductResource::collection($items);
    }

    public function warehouseIssuances(InventoryServices $services)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['name', 'description', 'quantity', 'code', 'brand'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])]
        ]);

        $histories = InventoryTransaction::query()
            ->with('inventory', 'inventory.product', 'inventory.location')
            ->where('movement', 'out')
            ->when(
                request('date_from') && request('date_to'),
                function (Builder $q) {
                    $date_from = request('date_from');
                    $date_to = request('date_to');
                    return $q->whereBetween('created_at', [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
                }
            )
            ->get();
        return response(['data' => $histories, 'histories' => $histories]);
        // return ProductResource::collection($services->getItemCosting());
    }

    public function inputsOfReceipts(InventoryServices $services)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['name', 'description', 'quantity', 'code', 'brand'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])]
        ]);

        $histories = InventoryTransaction::query()->with('inventory', 'inventory.product', 'inventory.location')->where('movement', 'in')->get();
        return response(['data' => $histories, 'histories' => $histories]);
        // return ProductResource::collection($services->getItemCosting());
    }

    public function branchInventory(InventoryServices $services)
    {
        request()->validate([
            'column' => ['nullable', Rule::in(['name', 'description', 'quantity', 'code', 'brand'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])]
        ]);
        // return ProductResource::collection($services->getBranchInventory());
        return InventoryLocationResource::collection($services->getBranchInventory());
    }

    public function histories($id, InventoryServices $services)
    {
        $inventoryIDS = Inventory::query()->where('inventory_location_id', $id)->pluck('id');
        $histories = InventoryTransaction::query()->whereIn('inventory_id', $inventoryIDS)->get();
        return response([
            'data' => InventoryTransactionResource::collection($histories->load(
                ['receive', 'request', 'inventory']
            )),
            'inventory' => $inventoryIDS
        ]);
        // return  $inventory;//
        // return InventoryResource::collection($services->getHistories($id));
    }
    public function getLowStocks(InventoryServices $services)
    {
        return ProductResource::collection($services->getLowStock());
    }
    public function getEmptyStocks(InventoryServices $services)
    {
        return ProductResource::collection($services->getEmptyStock());
    }
    public function status(InventoryServices $services)
    {
        return [
            'low' => ProductResource::collection($services->getLowStock()),
            'empty' => ProductResource::collection($services->getEmptyStock()),
            'pending' => $services->countItemWithNoInventoryRecords()
        ];
    }
    public function updateBeginningBalance(InventoryServices $inventory_services, $id)
    {
        try {
            DB::beginTransaction();
            $user = request()->user();
            $data = [
                'transacted_by_id' => $user->id,
                'accepted_by_id' => $user->id,
                'from_branch_id' => $user->branch_id,
                'to_branch_id' => $user->branch_id,
                'price' => request('price'),
                'description' => 'updated beginning balance'
            ];
            $stock_in = $inventory_services->stockIn(request('product_id'), request('qty'), $data);

            DB::commit();
            return response(['stock_in' => $stock_in]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 200);
        }
    }
    public function repackItem(InventoryServices $inventory_services)
    {
        try {
            DB::beginTransaction();
            $user = request()->user();
            $data = [
                'transacted_by_id' => $user->id,
                'accepted_by_id' => $user->id,
                'from_branch_id' => $user->branch_id,
                'to_branch_id' => $user->branch_id,
                'description' => 'repack item'
            ];
            $stock_in = $inventory_services->stockIn(request('output_product_id'), request('output_qty'), $data);
            $stock_out = $inventory_services->stockOut(request('product_id'), request('qty'), $data);

            DB::commit();
            return response(['stock_in' => $stock_in, 'stock_out' => $stock_out]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['error' => $e->getMessage(), 'data' => request()->all(), 'type' => 'error', 'message' => 'Error processing your action.'], 200);
        }
    }
    public function populateInventory(InventoryServices $inventoryServices)
    {
        return $inventoryServices->populateInventories();
    }

    public function dashboardData()
    {
        // $inventoryServices->populateInventories();
        $user = request()->user();
        $rc_exclude_purpose = ['production', 'project_plant', 'stocking', 'for_purchase'];

        $requests_received = Requisition::query()->where('branch_id', '!=', 1)
            ->whereNotIn('purpose', $rc_exclude_purpose)
            ->get()
            ->count();
        $branch_users = User::query()->where('branch_id', $user->branch_id)->pluck('id');
        $requests_accepted = Requisition::query()->whereIn('accepted_by_id', $branch_users)->get()->count();

        $materials_received = InventoryTransaction::where('branch_id', $user->branch_id)
            ->where('details', 'item issuance')
            ->where('movement', 'in')
            ->get()
            ->count();

        $materials_issued = InventoryTransaction::where('branch_id', $user->branch_id)
            ->where('details', 'item issuance')
            ->where('movement', 'out')
            ->get()
            ->count();

        $materials_returned = InventoryTransaction::where('branch_id', $user->branch_id)
            ->where('details', 'return materials to main warehouse')
            ->where('movement', 'out')
            ->get()
            ->count();
        $fast_moving_items = InventoryTransaction::select('inventory_id', DB::raw('count(*) as total'))
            ->with('inventory', 'inventory.product', 'inventory.location')
            ->where('branch_id', $user->branch_id)
            ->groupBy('inventory_id')
            ->get();

        $levels_per_product = InventoryLocation::query()
            ->with('inventory', 'inventory.product')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->where('branch_id', $user->branch_id)
            ->get();

        $levels_per_branch = InventoryLocation::query()
            ->with('inventory', 'inventory.product', 'location')
            ->orderBy('updated_at', 'desc')
            ->when(
                $user->branch_id != 1,
                function (Builder $q) use ($user) {
                    return $q->where('branch_id', $user->branch_id);
                }
            )
            ->limit(10)
            ->get();
        return response(['request_received' => $requests_received, 'requests_accepted' => $requests_accepted, 'materials_received' => $materials_received, 'materials_issued' => $materials_issued, 'materials_returned' => $materials_returned, 'fast_moving_items' => $fast_moving_items, 'levels_per_product' => $levels_per_product, 'levels_per_branch' => $levels_per_branch, $user->branch_id]);
    }

    public function getFastMovingItems()
    {
        $user = request()->user();
        $fast_moving_items = InventoryTransaction::select('inventory_id', DB::raw('count(*) as total'))
            ->with('inventory', 'inventory.product', 'inventory.location')
            ->where('branch_id', $user->branch_id)
            ->groupBy('inventory_id')
            ->when(
                request()->get('column') && request()->get('direction'),
                fn ($q) => $q->orderBy(request()->get('column'), request()->get('direction'))
            )

            ->paginate(request('paginate') ? (request('paginate') == 'all' ?  -1 : request('paginate')) : 10);

        return InventoryTransactionResource::collection($fast_moving_items);
    }

    // public function getFastMovingItems()
    // {
    //     $user = request()->user();

    //     $levels_per_product = InventoryLocation::query()
    //         ->with('inventory', 'inventory.product')
    //         ->orderBy('updated_at', 'desc')
    //         ->where('branch_id', $user->branch_id) 
    //         ->when(
    //             request()->get('column') && request()->get('direction'),
    //             fn ($q) => $q->orderBy(request()->get('column'), request()->get('direction'))
    //         )

    //         ->paginate(request('paginate', 10) ? (request('paginate') == 'all' ?  -1 : 10) : 10);

    //     return InventoryTransactionResource::collection($levels_per_product);
    // }
}
