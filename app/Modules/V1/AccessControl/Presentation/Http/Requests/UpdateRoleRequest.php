<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::names($this->portal()))],
        ];
    }

    private function portal(): string
    {
        return str_contains($this->path(), 'company/access-control')
            ? PermissionCatalog::COMPANY_PORTAL
            : PermissionCatalog::ADMIN_PORTAL;
    }
}
