<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;
use App\Modules\V1\Companies\Presentation\Http\Requests\RegisterCompanyRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = request('type');
        return match ($type) {
            PortalTypeEnum::COMPANY->value => (new RegisterCompanyRequest())->rules(),
        };
    }
}
