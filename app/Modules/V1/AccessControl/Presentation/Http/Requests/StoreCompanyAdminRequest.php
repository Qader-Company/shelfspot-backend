<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('portal', PermissionCatalog::COMPANY_PORTAL)->where('company_id', $this->companyId())],
        ];
    }

    private function companyId(): int
    {
        return app(TenantContextInterface::class)->getCompanyId();
    }
}
