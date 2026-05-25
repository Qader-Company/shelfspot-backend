<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $companies = [
            [
                'name' => 'Nile Foods',
                'cr_number' => 'CR-100200',
                'email' => 'contact@nilefoods.com',
                'phone' => '+966500000001',
                'industry' => 'FMCG',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
                'cash_on_hand' => 0,
            ],
            [
                'name' => 'Atlas Care',
                'cr_number' => 'CR-300400',
                'email' => 'contact@atlascare.com',
                'phone' => '+966500000002',
                'industry' => 'Personal Care',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
                'cash_on_hand' => 0,
            ],
        ];

        foreach ($companies as $companyData) {
            DB::table('companies')->updateOrInsert(
                ['email' => $companyData['email']],
                array_merge($companyData, [
                    'slug' => Str::slug($companyData['name']),
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );

            $company = DB::table('companies')->where('email', $companyData['email'])->first();

            if (! $company) {
                continue;
            }

            $this->seedCompanyCatalog((int) $company->id, $companyData['name'], $now);
        }
    }

    private function seedCompanyCatalog(int $companyId, string $companyName, $now): void
    {
        $catalog = [
            'Nile Foods' => [
                'brand' => 'Nile Fresh',
                'sub_brand' => 'Nile Fresh Lite',
                'category' => 'Dairy',
                'sub_category' => 'Yogurt',
                'products' => [
                    ['name' => 'Nile Fresh Lite Greek Yogurt 170g', 'sku' => 'NF-GY-170'],
                    ['name' => 'Nile Fresh Lite Strawberry Yogurt 150g', 'sku' => 'NF-SY-150'],
                ],
            ],
            'Atlas Care' => [
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

        $data = $catalog[$companyName] ?? null;

        if (! $data) {
            return;
        }

        $brandSlug = Str::slug($data['brand']);
        DB::table('brands')->updateOrInsert(
            ['company_id' => $companyId, 'slug' => $brandSlug],
            ['name' => $data['brand'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        $brandId = (int) DB::table('brands')->where('company_id', $companyId)->where('slug', $brandSlug)->value('id');

        $subBrandSlug = Str::slug($data['sub_brand']);
        DB::table('sub_brands')->updateOrInsert(
            ['company_id' => $companyId, 'slug' => $subBrandSlug],
            ['brand_id' => $brandId, 'name' => $data['sub_brand'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        $subBrandId = (int) DB::table('sub_brands')->where('company_id', $companyId)->where('slug', $subBrandSlug)->value('id');

        $categorySlug = Str::slug($data['category']);
        DB::table('categories')->updateOrInsert(
            ['company_id' => $companyId, 'slug' => $categorySlug],
            ['brand_id' => $brandId, 'sub_brand_id' => $subBrandId, 'name' => $data['category'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );
        $categoryId = (int) DB::table('categories')->where('company_id', $companyId)->where('slug', $categorySlug)->value('id');

        $subCategorySlug = Str::slug($data['sub_category']);
        DB::table('sub_categories')->updateOrInsert(
            ['company_id' => $companyId, 'slug' => $subCategorySlug],
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
        $subCategoryId = (int) DB::table('sub_categories')->where('company_id', $companyId)->where('slug', $subCategorySlug)->value('id');

        foreach ($data['products'] as $product) {
            DB::table('products')->updateOrInsert(
                ['company_id' => $companyId, 'slug' => Str::slug($product['name'])],
                [
                    'brand_id' => $brandId,
                    'sub_brand_id' => $subBrandId,
                    'category_id' => $categoryId,
                    'sub_category_id' => $subCategoryId,
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'description' => 'Seeded demo product for '.$companyName.'.',
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
