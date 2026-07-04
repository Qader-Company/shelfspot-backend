<?php

namespace Database\Seeders;

use App\Modules\V1\Companies\Domain\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        $this->seedCompanyCatalog($company->id , $company->name, now());

    }

    private function seedCompanyCatalog(int $companyId, string $companyName, $now): void
    {
        $catalog = [
             [
                'brand' => 'Nile Fresh',
                'sub_brand' => 'Nile Fresh Lite',
                'category' => 'Dairy',
                'sub_category' => 'Yogurt',
                'products' => [
                    ['name' => 'Nile Fresh Lite Greek Yogurt 170g', 'sku' => 'NF-GY-170'],
                    ['name' => 'Nile Fresh Lite Strawberry Yogurt 150g', 'sku' => 'NF-SY-150'],
                ],
            ],
            [
                'brand' => 'Atlas Home',
                'sub_brand' => 'Atlas Home Pro',
                'category' => 'Cleaning',
                'sub_category' => 'Surface Cleaners',
                'products' => [
                    ['name' => 'Atlas Home Pro Lemon Surface Cleaner 1L', 'sku' => 'AH-LSC-1L'],
                    ['name' => 'Atlas Home Pro Lavender Surface Cleaner 1L', 'sku' => 'AH-VSC-1L'],
                ],
            ],
        ];
        foreach ($catalog as $data) {
            DB::table('brands')->updateOrInsert(
                ['company_id' => $companyId, 'name' => $data['brand']],
                ['name' => $data['brand'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
            $brandId = (int) DB::table('brands')->where('company_id', $companyId)->where('name', $data['brand'])->value('id');

            DB::table('sub_brands')->updateOrInsert(
                ['company_id' => $companyId, 'name' => $data['sub_brand']],
                ['brand_id' => $brandId, 'name' => $data['sub_brand'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
            $subBrandId = (int) DB::table('sub_brands')->where('company_id', $companyId)->where('name', $data['sub_brand'])->value('id');

            DB::table('categories')->updateOrInsert(
                ['company_id' => $companyId, 'name' => $data['category']],
                ['brand_id' => $brandId, 'sub_brand_id' => $subBrandId, 'name' => $data['category'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
            $categoryId = (int) DB::table('categories')->where('company_id', $companyId)->where('name', $data['category'])->value('id');

            DB::table('sub_categories')->updateOrInsert(
                ['company_id' => $companyId, 'name' => $data['sub_category']],
                [
                    'brand_id' => $brandId,
                    'sub_brand_id' => $subBrandId,
                    'category_id' => $categoryId,
                    'name' => $data['sub_category'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $subCategoryId = (int) DB::table('sub_categories')->where('company_id', $companyId)->where('name', $data['sub_category'])->value('id');

            foreach ($data['products'] as $product) {
                DB::table('products')->updateOrInsert(
                    ['company_id' => $companyId, 'name' => $product['name']],
                    [
                        'brand_id' => $brandId,
                        'sub_brand_id' => $subBrandId,
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategoryId,
                        'name' => $product['name'],
                        'sku' => $product['sku'],
                        'barcode' => $product['barcode'] ?? null,
                        'description' => 'Seeded demo product for '.$companyName.'.',
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
