<?php

namespace App\Http\Controllers\Managements;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

class BranchesController extends Controller
{

    public function index()
    {
        return BranchResource::collection(
            Branch::query()->latest()->when(request('keyword'),
            function(Builder $q){
                $keyword = request('keyword');
                return $q->whereRaw("CONCAT_WS(' ',name,address,code) like '%{$keyword}%' ");
            })->get()
        );
    }

    public function store(BranchRequest $request)
    {
        return BranchResource::make(
            Branch::query()->create($request->validated())
        );
    }

    public function update(BranchRequest $request, int $id)
    {
        $branch = Branch::query()->findOrFail($id);
        $branch->fill($request->validated());
        $branch->save();
        return BranchResource::make($branch);
    }

    public function destroy(int $id)
    {
        $branch = Branch::query()->findOrFail($id);
        $branch->delete();
        return BranchResource::make($branch);
    }
}