<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requisition_items_resolve_inventory_by_branch_id(): void
    {
        $requestingBranch = Branch::query()->create([
            'name' => 'Requesting Branch',
            'address' => 'Cebu City',
            'code' => 'REQ-0001',
        ]);

        $sourceBranch = Branch::query()->create([
            'name' => 'Source Branch',
            'address' => 'Cebu City',
            'code' => 'SRC-0001',
        ]);

        $category = Category::query()->create([
            'name' => 'Relation Test Category',
        ]);

        $product = Product::query()->create([
            'name' => 'Relation Test Product',
            'code' => 'REL-0001',
            'description' => 'Relation test product',
            'unit_measurement' => 'PCS',
            'unit_value' => 1,
            'stock_low_level' => 0,
            'reorder_point' => 0,
            'brand' => 'MRII',
            'category_id' => $category->id,
            'account_code' => 'ACC-001',
        ]);

        $user = User::factory()->create([
            'branch_id' => $requestingBranch->id,
            'email' => 'relation-test@example.com',
            'username' => 'relation-test-user',
        ]);

        $inventoryLocation = InventoryLocation::query()->create([
            'product_id' => $product->id,
            'branch_id' => $sourceBranch->id,
            'price' => 100,
            'total_quantity' => 10,
            'quantity' => 10,
        ]);

        $requisition = new Requisition();
        $requisition->project_name = 'Relation test';
        $requisition->status = 'pending';
        $requisition->branch_id = $requestingBranch->id;
        $requisition->user_id = $user->id;
        $requisition->purpose = 'finished_goods';
        $requisition->account_code = 'ACC-001';
        $requisition->project_code = 'PRJ-001';
        $requisition->save();

        $detail = RequisitionDetail::query()->create([
            'requisition_id' => $requisition->id,
            'location_id' => $sourceBranch->id,
        ]);

        $item = RequisitionItem::query()->create([
            'requisition_detail_id' => $detail->id,
            'product_id' => $product->id,
            'request_quantity' => 2,
            'full_filled_quantity' => 0,
            'status' => 'incomplete',
        ]);

        $this->assertNotNull($item->inventory);
        $this->assertSame($inventoryLocation->id, $item->inventory->id);
        $this->assertSame($sourceBranch->id, $item->inventory->branch_id);
    }
}
