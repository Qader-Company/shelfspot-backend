<?php
namespace App\Modules\V1\SubCategories\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    use Filterable;
    public function index(){ $f=$this->acceptedFilters(request(),['name','is_active','brand_id','sub_brand_id','category_id']); $q=SubCategory::query()->with('media')->when($f,fn($q)=>$q->filter($f)); return ApiResponse::success($q->paginate(request('per_page',15))); }
    public function show(string $id){ $m=SubCategory::with('media')->findOrFail($id); return ApiResponse::success(array_merge($m->toArray(), ['image' => $m->getMedia('image')->first()?->getUrl()])); }
    public function store(Request $request){ $data=$request->validate(['name'=>'required|string|max:255','brand_id'=>'nullable|exists:brands,id','sub_brand_id'=>'nullable|exists:sub_brands,id','category_id'=>'required|exists:categories,id','image'=>'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048','is_active'=>'required|boolean']); $m=SubCategory::create($data); if(isset($data['image'])) $m->addMedia($data['image'])->toMediaCollection('image'); return ApiResponse::message(__('apiMessage.created')); }
    public function update(Request $request,string $id){ $data=$request->validate(['name'=>'sometimes|string|max:255','brand_id'=>'nullable|exists:brands,id','sub_brand_id'=>'nullable|exists:sub_brands,id','category_id'=>'sometimes|exists:categories,id','image'=>'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048','is_active'=>'sometimes|boolean']); $m=SubCategory::findOrFail($id); $m->update($data); if(isset($data['image'])) { $m->clearMediaCollection('image'); $m->addMedia($data['image'])->toMediaCollection('image'); } return ApiResponse::message(__('apiMessage.updated')); }
    public function destroy(string $id){ $m=SubCategory::findOrFail($id); $m->clearMediaCollection('image'); $m->delete(); return ApiResponse::deleted(); }
}
