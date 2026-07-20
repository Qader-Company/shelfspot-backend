<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\Shared\Presentation\Http\Requests\Concerns\ValidatesSingleMediaUpdate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubBrandRequest extends FormRequest
{
    use ValidatesSingleMediaUpdate;
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', new ExistsInCurrentCompany('brands')],
            'translations' => 'sometimes|array',
            'translations.en.name' => 'sometimes|required|string|max:255',
            'translations.ar.name' => 'sometimes|required|string|max:255',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ...$this->singleMediaActionRules('logo_action'),
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function after(): array
    {
        return [$this->validateSingleMediaUpdate('logo', 'logo_action')];
    }

}
