<?php

namespace App\Modules\V1\CompanyAdmins\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateCompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where('type', PortalTypeEnum::COMPANY->value)
                    ->ignore((int) $this->route('user')),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => [
                'string',
                'distinct',
                Rule::exists('roles', 'name')
                    ->where('portal', PermissionCatalog::COMPANY_PORTAL)
                    ->where('company_id', $this->companyId()),
            ],
        ];
    }

    private function companyId(): int
    {
        return app(TenantContextInterface::class)->getCompanyId();
    }
}
