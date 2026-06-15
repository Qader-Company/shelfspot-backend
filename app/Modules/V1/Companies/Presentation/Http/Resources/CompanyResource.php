<?php

namespace App\Modules\V1\Companies\Presentation\Http\Resources;

use App\Modules\V1\Companies\Presentation\Http\Resources\CompanyUserResource;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deleted_at' => $this->deleted_at?->toISOString(),
            'name' => $this->name ,
            'slug' => $this->slug ,
            'email' => $this->email ,
            'phone' => $this->phone ,
            'cr_number' => $this->cr_number ,
            'industry' => $this->industry->label() ,
            'is_active' => $this->is_active,
            'users' => CompanyUserResource::collection($this->whenLoaded('users')),
            'latest_tasks' => TaskResource::collection($this->whenLoaded('latestTasks')),
            'total_requests_count' => $this->when(isset($this->total_requests_count), (int) $this->total_requests_count),
            'completed_requests_count' => $this->when(isset($this->completed_requests_count), (int) $this->completed_requests_count),
            'pending_requests_count' => $this->when(isset($this->pending_requests_count), (int) $this->pending_requests_count),
            'total_spending' => $this->when(isset($this->total_spending), (float) $this->total_spending),
            'total_products_count' => $this->when(isset($this->total_products_count), (int) $this->total_products_count),
        ];
    }
}
