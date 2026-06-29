<?php

namespace App\Modules\Shared\Application\Excel;

use App\Modules\Shared\Application\Excel\CatalogExcelResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

abstract class AbstractCatalogImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    private const MAX_ROWS = 1000;

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private array $errors = [];
    private array $parentCaches = [];
    private int $totalRows = 0;

    public function __construct(private readonly array $config)
    {
    }

    public function collection(Collection $rows): void
    {
        $this->totalRows = $rows->count();

        if ($this->totalRows > self::MAX_ROWS) {
            $this->addError(0, ['The uploaded file exceeds the maximum allowed rows.'], 'file');
            return;
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());

                if ($this->isEmptyRow($data)) {
                    $this->skipped++;
                    continue;
                }

                $attributes = $this->attributesFromRow($data, $rowNumber);

                if ($attributes === null) {
                    continue;
                }

                $validator = Validator::make($attributes, $this->rules());

                if ($validator->fails()) {
                    $this->addValidationErrors($rowNumber, $validator->errors()->messages());
                    continue;
                }

                if (! $this->validateRelations($attributes, $rowNumber)) {
                    continue;
                }

                if (! $this->validateUniqueFields($attributes, $rowNumber)) {
                    continue;
                }

                $this->persist($attributes, $rowNumber);
            }
        });
    }

    public function result(): CatalogExcelResult
    {
        return new CatalogExcelResult($this->created, $this->updated, $this->skipped, $this->errors, $this->totalRows);
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[str_replace(' ', '_', trim((string) $key))] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($this->config['headings'] as $heading) {
            if (filled($row[$heading] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function attributesFromRow(array $row, int $rowNumber): ?array
    {
        $attributes = ['id' => $row['id'] ?? null];

        foreach ($this->config['fillable'] as $field) {
            if ($field === 'is_active') {
                $attributes[$field] = $this->booleanValue($row['is_active'] ?? true);
                continue;
            }

            if (str_ends_with($field, '_id')) {
                continue;
            }

            $attributes[$field] = $row[$field] ?? null;
        }

        foreach ($this->config['parents'] as $heading => $parent) {
            $value = $row[$heading] ?? null;
            $parentId = $this->resolveParentId($parent['model'], $value);

            if ($parent['required'] && $parentId === null) {
                $this->addError($rowNumber, ["The {$heading} column is required and must match one of the template dropdown values."], $heading);
                return null;
            }

            if (filled($value) && $parentId === null) {
                $this->addError($rowNumber, ["The selected {$heading} value does not exist for the current company."], $heading);
                return null;
            }

            $attributes[$parent['attribute']] = $parentId;
        }

        return $attributes;
    }

    private function rules(): array
    {
        $rules = [
            'id' => ['nullable', 'integer'],
            'is_active' => ['required', 'boolean'],
        ];

        if (in_array('name', $this->config['fillable'], true)) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        if (in_array('sku', $this->config['fillable'], true)) {
            $rules['sku'] = ['nullable', 'string', 'max:255'];
        }

        if (in_array('barcode', $this->config['fillable'], true)) {
            $rules['barcode'] = ['nullable', 'string', 'max:255'];
        }

        if (in_array('description', $this->config['fillable'], true)) {
            $rules['description'] = ['nullable', 'string'];
        }

        foreach ($this->config['parents'] as $parent) {
            $rules[$parent['attribute']] = [$parent['required'] ? 'required' : 'nullable', 'integer'];
        }

        return $rules;
    }

    private function booleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return true;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'y', 'active', 'enabled' => true,
            '0', 'false', 'no', 'n', 'inactive', 'disabled' => false,
            default => null,
        };
    }

    private function resolveParentId(string $modelClass, mixed $value): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $cache = $this->parentCaches[$modelClass] ??= $modelClass::query()->get()->mapWithKeys(function (Model $model): array {
            return [
                (string) $model->getKey() => $model->getKey(),
                strtolower($model->getAttribute('name')) => $model->getKey(),
                strtolower($model->getKey().' - '.$model->getAttribute('name')) => $model->getKey(),
            ];
        })->all();

        $normalized = strtolower(trim((string) $value));

        if (preg_match('/^(\d+)\s*-/', $normalized, $matches)) {
            $normalized = $matches[1];
        }

        return isset($cache[$normalized]) ? (int) $cache[$normalized] : null;
    }

    private function validateRelations(array $attributes, int $rowNumber): bool
    {
        if (($attributes['sub_brand_id'] ?? null) && ! ($attributes['brand_id'] ?? null)) {
            $this->addError($rowNumber, ['The brand column is required when sub_brand is selected.'], 'brand');
            return false;
        }

        if (($attributes['sub_category_id'] ?? null) && ! ($attributes['category_id'] ?? null)) {
            $this->addError($rowNumber, ['The category column is required when sub_category is selected.'], 'category');
            return false;
        }

        if (isset($attributes['brand_id'], $attributes['sub_brand_id']) && $attributes['brand_id'] && $attributes['sub_brand_id']) {
            $subBrand = $this->findParent($this->config['parents']['sub_brand']['model'] ?? null, $attributes['sub_brand_id']);
            if ($subBrand && (int) $subBrand->brand_id !== (int) $attributes['brand_id']) {
                $this->addError($rowNumber, ['The selected sub_brand does not belong to the selected brand.'], 'sub_brand');
                return false;
            }
        }

        if (isset($attributes['category_id'], $attributes['sub_category_id']) && $attributes['category_id'] && $attributes['sub_category_id']) {
            $subCategory = $this->findParent($this->config['parents']['sub_category']['model'] ?? null, $attributes['sub_category_id']);
            if ($subCategory && (int) $subCategory->category_id !== (int) $attributes['category_id']) {
                $this->addError($rowNumber, ['The selected sub_category does not belong to the selected category.'], 'sub_category');
                return false;
            }
        }

        return true;
    }

    private function findParent(?string $modelClass, int $id): ?Model
    {
        if ($modelClass === null) {
            return null;
        }

        return $modelClass::query()->find($id);
    }

    private function validateUniqueFields(array $attributes, int $rowNumber): bool
    {
        if (! array_key_exists('sku', $attributes) || ! filled($attributes['sku'])) {
            return true;
        }

        if (! filled($attributes['id'] ?? null)) {
            return true;
        }

        $modelClass = $this->config['model'];
        $exists = $modelClass::query()
            ->where('sku', $attributes['sku'])
            ->whereKeyNot($attributes['id'])
            ->exists();

        if ($exists) {
            $this->addError($rowNumber, ['The sku has already been used for another product in the current company.'], 'sku');
            return false;
        }

        return true;
    }

    private function persist(array $attributes, int $rowNumber): void
    {
        $modelClass = $this->config['model'];
        $id = Arr::pull($attributes, 'id');
        $model = null;

        if (filled($id)) {
            $model = $modelClass::query()->find($id);

            if (! $model) {
                $this->addError($rowNumber, ["No {$this->config['module']} record was found with id {$id} for the current company."], 'id');
                return;
            }
        }

        if (! $model && filled($attributes['sku'] ?? null)) {
            $model = $modelClass::query()->where('sku', $attributes['sku'])->first();
        }

        if ($model) {
            $model->update($attributes);
            $this->updated++;
            return;
        }

        $modelClass::query()->create($attributes);
        $this->created++;
    }

    private function addValidationErrors(int $rowNumber, array $errors): void
    {
        foreach ($errors as $column => $messages) {
            $this->addError($rowNumber, $messages, $column);
        }
    }

    private function addError(int $rowNumber, array $messages, ?string $column = null): void
    {
        $error = [
            'row' => $rowNumber,
            'messages' => array_values($messages),
        ];

        if ($column !== null) {
            $error['column'] = $column;
        }

        $this->errors[] = $error;
    }
}
