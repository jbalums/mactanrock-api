<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitsResource;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

class UnitsController extends Controller
{

    public function index()
    {
        return UnitsResource::collection(Unit::query()->when(
            request('keyword'),
            function ($q) {
                $keyword = request('keyword');
                return $q->whereRaw("CONCAT_WS(' ',name,description) like '%{$keyword}%' ");
            }
        )->orderBy('name', 'asc')->get());
    }

    public function store(UnitRequest $request)
    {
        $Units = new Unit;
        $Units->name = $request->name;
        $Units->description = $request->description;
        $Units->save();
        return UnitsResource::make($Units);
    }

    public function update(UnitRequest $request, int $id)
    {
        $Units = Unit::query()->findOrFail($id);
        $Units->name = $request->name;
        $Units->description = $request->description;
        $Units->save();
        return UnitsResource::make($Units);
    }

    public function destroy(int $id)
    {
        $Units = Unit::query()->findOrFail($id);
        $Units->delete();
        return UnitsResource::make($Units);
    }
}
