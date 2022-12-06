<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierBank;
use App\Models\SupplierContact;
use Illuminate\Http\Request;

class SupplierServices
{

    public function create(Request $request)
    {
        $supplier = new Supplier();
        return $this->supplierData($request, $supplier);

    }

    public function update(Request $request, int $id)
    {
        $supplier = Supplier::query()->findOrFail($id);
        return $this->supplierData($request, $supplier);
    }


    private function contacts(Request $request, int $id)
    {
        SupplierContact::query()->where('supplier_id', $id)->delete();

        $contacts = [];
        foreach ($request->get('contacts') as $contact){
            $contacts[] = [
                'name' => $contact['name'],
                'email' => $contact['email'] ?? "",
                'number' => $contact['number'] ?? "",
                'supplier_id' => $id,
            ];
        }

        SupplierContact::query()->insert($contacts);
    }

    private function banks(Request $request, int $id)
    {
        SupplierBank::query()->where('supplier_id', $id)->delete();

        $banks = [];
        foreach ($request->get('banks') as $bank){
            $banks[] = [
                'name' => $bank['name'],
                'account_name' => $bank['account_name'],
                'account_number' => $bank['account_number'],
                'supplier_id' => $id,
            ];
        }

        SupplierBank::query()->insert($banks);
    }

    /**
     * @param Request $request
     * @param \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null $supplier
     * @return array|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function supplierData(Request $request, \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null $supplier): array|null|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $supplier->name = $request->get('name');
        $supplier->address = $request->get('address');
        $supplier->street = $request->get('street');
        $supplier->code = $request->get('code');
        $supplier->owner = $request->get('owner');
        $supplier->tin = $request->get('tin');
        $supplier->save();

        $this->contacts($request, $supplier->id);
        $this->banks($request, $supplier->id);

        $supplier->load(['banks', 'contacts']);
        return $supplier;
    }
}