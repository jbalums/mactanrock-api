<?php

namespace App\Services;

use App\Enums\ReceivingStatus;
use App\Models\Receive;
use App\Models\ReceiveDetail;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ReceivingService
{
    public function get(?int $branch_id = null)
    {
        return Receive::query()
            ->with(['branch', 'details' => ['product'], 'supplier'])

            ->when(request('keyword'), function (Builder $q) {
                $keyword = request('keyword');
                return $q->whereRaw("CONCAT_WS(' ',purchase_order,project_name,status,account_code) like '%{$keyword}%' ");
            })
            ->when(request('date'), function (Builder $q) {
                return $q->where('date_receive', request('date'));
            })
            ->when(!is_null($branch_id), fn($q) => $q->where('branch_id', $branch_id))
            ->latest()
            ->paginate(is_integer(request()->get('paginate')) ?? 1000000);
    }
    public function create(Request $request)
    {
        $receiving = new Receive();
        $receiving->purchase_order = $request->get('purchase_order');
        $receiving->supplier_id = $request->get('supplier_id');
        $receiving->project_name = $request->get('project_name');
        $receiving->branch_id = $request->get('branch_id', request()->user()->branch_id);
        $receiving->date_receive = $request->get('date_receive');
        $receiving->status = $request->get('status', ReceivingStatus::Pending);
        $receiving->save();

        $details = request()->get('products', []);
        $items = [];

        foreach ($details as $key => $product) {
            $items[] = [
                'product_id' => $product,
                'receive_id' => $receiving->id,
                'quantity' => request()->get('quantity')[$key] ?? 0,
                'price' => request()->get('price')[$key] ?? 0,
                'expired_at' => request()->get('expired_at')[$key] ?? null,
            ];
        }

        ReceiveDetail::query()->insert($items);

        $receiving->load('details');
        return $receiving;
    }

    public function markCompleted(int $receive_id)
    {
        $receiving = Receive::query()
            ->with('details')
            ->where('status', ReceivingStatus::Pending)
            ->findOrFail($receive_id);
        $receiving->status = ReceivingStatus::Completed;
        $receiving->save();

        return $receiving;
    }

    public function show(int $id)
    {
        return Receive::query()
            ->with([
                'details' => ['product'],
            ])
            ->findOrFail($id);
    }
}
