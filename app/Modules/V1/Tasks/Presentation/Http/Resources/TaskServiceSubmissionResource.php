<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskServiceSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_service_id' => $this->task_service_id,
            'worker_id' => $this->worker_id,
            'form_data' => $this->form_data,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'attachments' => $this->getMedia()->map(fn ($media) => [
                'id' => $media->id,
                'field' => $media->getCustomProperty('field'),
                'collection' => $media->collection_name,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
