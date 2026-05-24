<?php
namespace App\Modules\V1\Categories\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Categories\Domain\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use Filterable;
    public function index(){ $f=$this->acceptedFilters(request(),['name','is_active','brand_id','sub_brand_id']); $q=Category::query()->when($f,fn($q)=>$q->filter($f)); return ApiResponse::success($q->paginate(request('per_page',15))); }
    public function show(string $id){ return ApiResponse::success(Category::findOrFail($id)); }
    public function store(Request $request){ $data=$request->validate(['name'=>'required|string|max:255','brand_id'=>'nullable|exists:brands,id','sub_brand_id'=>'nullable|exists:sub_brands,id','is_active'=>'required|boolean']); Category::create($data); return ApiResponse::message(__('apiMessage.created')); }
    public function update(Request $request,string $id){ $data=$request->validate(['name'=>'sometimes|string|max:255','brand_id'=>'nullable|exists:brands,id','sub_brand_id'=>'nullable|exists:sub_brands,id','is_active'=>'sometimes|boolean']); $m=Category::findOrFail($id); $m->update($data); return ApiResponse::message(__('apiMessage.updated')); }
    public function destroy(string $id){ Category::findOrFail($id)->delete(); return ApiResponse::deleted(); }
}
