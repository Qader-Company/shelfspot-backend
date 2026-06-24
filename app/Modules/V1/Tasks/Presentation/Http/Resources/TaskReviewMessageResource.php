<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskReviewMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'sender_role' => $this->sender_role,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender?->id,
                'name' => $this->sender?->name,
                'email' => $this->sender?->email,
            ]),
            'message' => $this->message,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
