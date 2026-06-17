<?php

namespace App\Modules\V1\Admins\Presentation\Http\Requests;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShelfSpotAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->where('type', PortalTypeEnum::ADMIN->value)],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('portal', PermissionCatalog::ADMIN_PORTAL)->where('company_id', null)],
        ];
    }
}
