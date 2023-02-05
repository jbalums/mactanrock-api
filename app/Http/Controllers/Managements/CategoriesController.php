<?php

namespace App\Http\Controllers\Managements;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoriesController extends \App\Http\Controllers\Controller
{

    public function index()
    {
        return CategoryResource::collection(Category::query()->when(request('keyword'),
        function(Builder $q){
            $keyword = request('keyword');
            return $q->whereRaw("CONCAT_WS(' ',name,gl_account) like '%{$keyword}%' ");
        })->orderBy('name','asc')->get());
    }

    public function store(CategoryRequest $request)
    {
        $category = new Category;
        $category->name = $request->name;
        $category->gl_account = $request->gl_account;
        $category->save();
        return CategoryResource::make($category);
    }

    public function update(CategoryRequest $request, int $id)
    {
        $category = Category::query()->findOrFail($id);
        $category->name = $request->name;
        $category->gl_account = $request->gl_account;
        $category->save();
        return CategoryResource::make($category);
    }

    public function destroy(int $id)
    {
        $category = Category::query()->findOrFail($id);
        $category->delete();
        return CategoryResource::make($category);
    }
}