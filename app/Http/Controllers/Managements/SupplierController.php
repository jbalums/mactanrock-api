<?php

namespace App\Http\Controllers\Managements;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierServices;

class SupplierController extends Controller
{
    public function index(SupplierServices $supplierServices)
    {
        return SupplierResource::collection(
           $supplierServices->getSuppliers()
        );
    }

    public function store(SupplierServices $supplierServices,SupplierRequest $request)
    {
        return SupplierResource::make(
           $supplierServices->create($request)
        );
    }

    public function update(SupplierServices $supplierServices,SupplierRequest $request, int $id)
    {

        return SupplierResource::make($supplierServices->update($request,$id));
    }

    public function show(int $id)
    {
        $supplier = Supplier::query()->with(['contacts','banks'])->findOrFail($id);
        return SupplierResource::make($supplier);
    }

    public function destroy(int $id)
    {
        $supplier = Supplier::query()->findOrFail($id);
        $supplier->banks()->delete();
        $supplier->contacts()->delete();
        $supplier->delete();
        return SupplierResource::make($supplier);
    }
}