<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\Models\TaskStatusHistory;
use App\Modules\V1\Tasks\Domain\Models\TaskWorkerAssignment;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCompanyCatalogSeeder extends Seeder
{
    /**
     * Seed a complete, reusable sample company catalog.
     */
    public function run(): void
    {
        $this->call(ServiceSeeder::class);

        DB::transaction(function (): void {
            $company = Company::query()->updateOrCreate(
                ['email' => 'catalog@shelfspot.test'],
                [
                    'name' => 'ShelfSpot Demo Market',
                    'phone' => '+201000000010',
                    'cr_number' => 'DEMO-CATALOG-001',
                    'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
                    'is_active' => true,
                ],
            );

            $owner = User::query()->updateOrCreate(
                ['email' => 'owner@shelfspot.test'],
                [
                    'name' => 'مالك شيلف سبوت',
                    'password' => 'password',
                    'type' => PortalTypeEnum::COMPANY,
                    'email_verified_at' => now(),
                ],
            );

            CompanyUser::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'user_id' => $owner->id,
                ],
                [
                    'is_owner' => true,
                    'is_active' => true,
                ],
            );
            app(FullAccessRoleProvisioner::class)->assignCompanyOwnerRole($owner, $company->id);

            $brand = Brand::query()->firstOrCreate([
                'company_id' => $company->id,
            ], ['is_active' => true]);
            $this->saveTranslation($brand, ['ar' => 'ماركت شيلف سبوت', 'en' => 'ShelfSpot Market']);

            $subBrand = SubBrand::query()->firstOrCreate([
                'company_id' => $company->id,
                'brand_id' => $brand->id,
            ], ['is_active' => true]);
            $this->saveTranslation($subBrand, ['ar' => 'العلامة الأساسية', 'en' => 'Core Collection']);

            $categories = [
                'beverages' => ['ar' => 'المشروبات', 'en' => 'Beverages'],
                'snacks' => ['ar' => 'الوجبات الخفيفة', 'en' => 'Snacks'],
            ];

            $subCategories = [];
            foreach ($categories as $key => $translations) {
                $category = Category::query()
                    ->where('company_id', $company->id)
                    ->where('brand_id', $brand->id)
                    ->where('sub_brand_id', $subBrand->id)
                    ->whereHas('translations', fn ($query) => $query
                        ->where('locale', 'en')
                        ->where('name', $translations['en']))
                    ->first()
                    ?? Category::query()->create([
                        'company_id' => $company->id,
                        'brand_id' => $brand->id,
                        'sub_brand_id' => $subBrand->id,
                        'is_active' => true,
                    ]);
                $this->saveTranslation($category, $translations);

                $subCategory = SubCategory::query()
                    ->where('company_id', $company->id)
                    ->where('brand_id', $brand->id)
                    ->where('sub_brand_id', $subBrand->id)
                    ->where('category_id', $category->id)
                    ->whereHas('translations', fn ($query) => $query
                        ->where('locale', 'en')
                        ->where('name', $translations['en']))
                    ->first()
                    ?? SubCategory::query()->create([
                        'company_id' => $company->id,
                        'brand_id' => $brand->id,
                        'sub_brand_id' => $subBrand->id,
                        'category_id' => $category->id,
                        'is_active' => true,
                    ]);
                $this->saveTranslation($subCategory, $translations);

                $subCategories[$key] = [$category, $subCategory];
            }

            $products = [
                ['sku' => 'DEMO-BEV-001', 'barcode' => '6221000000001', 'category' => 'beverages', 'ar' => 'مياه معدنية 600 مل', 'en' => 'Mineral Water 600 ml'],
                ['sku' => 'DEMO-BEV-002', 'barcode' => '6221000000002', 'category' => 'beverages', 'ar' => 'مياه غازية ليمون 330 مل', 'en' => 'Lemon Sparkling Water 330 ml'],
                ['sku' => 'DEMO-BEV-003', 'barcode' => '6221000000003', 'category' => 'beverages', 'ar' => 'عصير برتقال 250 مل', 'en' => 'Orange Juice 250 ml'],
                ['sku' => 'DEMO-BEV-004', 'barcode' => '6221000000004', 'category' => 'beverages', 'ar' => 'عصير مانجو 250 مل', 'en' => 'Mango Juice 250 ml'],
                ['sku' => 'DEMO-BEV-005', 'barcode' => '6221000000005', 'category' => 'beverages', 'ar' => 'مشروب طاقة 250 مل', 'en' => 'Energy Drink 250 ml'],
                ['sku' => 'DEMO-SNK-001', 'barcode' => '6221000000006', 'category' => 'snacks', 'ar' => 'رقائق بطاطس بالملح 50 جم', 'en' => 'Salted Potato Chips 50 g'],
                ['sku' => 'DEMO-SNK-002', 'barcode' => '6221000000007', 'category' => 'snacks', 'ar' => 'رقائق بطاطس بالجبنة 50 جم', 'en' => 'Cheese Potato Chips 50 g'],
                ['sku' => 'DEMO-SNK-003', 'barcode' => '6221000000008', 'category' => 'snacks', 'ar' => 'بسكويت شوكولاتة 40 جم', 'en' => 'Chocolate Cookies 40 g'],
                ['sku' => 'DEMO-SNK-004', 'barcode' => '6221000000009', 'category' => 'snacks', 'ar' => 'بار حبوب بالشوفان 35 جم', 'en' => 'Oat Cereal Bar 35 g'],
                ['sku' => 'DEMO-SNK-005', 'barcode' => '6221000000010', 'category' => 'snacks', 'ar' => 'فشار بالكراميل 60 جم', 'en' => 'Caramel Popcorn 60 g'],
            ];

            foreach ($products as $productData) {
                [$category, $subCategory] = $subCategories[$productData['category']];

                $product = Product::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'sku' => $productData['sku'],
                    ],
                    [
                        'brand_id' => $brand->id,
                        'sub_brand_id' => $subBrand->id,
                        'category_id' => $category->id,
                        'sub_category_id' => $subCategory->id,
                        'barcode' => $productData['barcode'],
                        'is_active' => true,
                    ],
                );

                $this->saveTranslation($product, [
                    'ar' => $productData['ar'],
                    'en' => $productData['en'],
                ], 'منتج تجريبي ضمن كتالوج الشركة', 'Sample product in the company catalog');
            }

            $workerUser = User::query()->updateOrCreate(
                ['email' => 'adel.elmashhoor@shelfspot.test'],
                [
                    'name' => 'عادل المشهور',
                    'password' => 'password',
                    'type' => PortalTypeEnum::WORKER,
                    'email_verified_at' => now(),
                ],
            );

            $worker = Worker::query()->updateOrCreate(
                ['user_id' => $workerUser->id],
                [
                    'phone' => '+201000000011',
                    'is_active' => true,
                    'last_latitude' => 30.0444200,
                    'last_longitude' => 31.2357120,
                    'last_location_name' => 'وسط البلد، القاهرة',
                    'location_updated_at' => now(),
                ],
            );

            $catalogProducts = Product::query()
                ->where('company_id', $company->id)
                ->whereIn('sku', collect($products)->pluck('sku'))
                ->get()
                ->keyBy('sku');

            $service = Service::query()->where('is_active', true)->firstOrFail();

            $this->seedTask(
                company: $company,
                owner: $owner,
                worker: null,
                service: $service,
                products: $catalogProducts->only(['DEMO-BEV-001', 'DEMO-BEV-002'])->values()->all(),
                key: 'draft',
                status: TaskStatusEnum::DRAFT,
                serviceStatus: TaskServiceStatusEnum::PENDING,
            );
            $this->seedTask(
                company: $company,
                owner: $owner,
                worker: null,
                service: $service,
                products: $catalogProducts->only(['DEMO-BEV-003', 'DEMO-BEV-004', 'DEMO-BEV-005'])->values()->all(),
                key: 'pending',
                status: TaskStatusEnum::PENDING,
                serviceStatus: TaskServiceStatusEnum::PENDING,
            );
            $this->seedTask(
                company: $company,
                owner: $owner,
                worker: $worker,
                service: $service,
                products: $catalogProducts->only(['DEMO-SNK-001', 'DEMO-SNK-002'])->values()->all(),
                key: 'in-progress',
                status: TaskStatusEnum::IN_PROGRESS,
                serviceStatus: TaskServiceStatusEnum::IN_PROGRESS,
            );
            $this->seedTask(
                company: $company,
                owner: $owner,
                worker: $worker,
                service: $service,
                products: $catalogProducts->only(['DEMO-SNK-003', 'DEMO-SNK-004', 'DEMO-SNK-005'])->values()->all(),
                key: 'review',
                status: TaskStatusEnum::COMPLETED,
                serviceStatus: TaskServiceStatusEnum::COMPLETED,
            );
        });
    }

    private function seedTask(
        Company $company,
        User $owner,
        ?Worker $worker,
        Service $service,
        array $products,
        string $key,
        TaskStatusEnum $status,
        TaskServiceStatusEnum $serviceStatus,
    ): void {
        $now = now();
        $isAssigned = $worker !== null;
        $isInProgress = $status === TaskStatusEnum::IN_PROGRESS;
        $isInReview = $status === TaskStatusEnum::COMPLETED;
        $notes = "[Demo catalog task: {$key}]";

        $task = Task::query()->firstOrNew([
            'company_id' => $company->id,
            'notes' => $notes,
        ]);
        $task->forceFill([
            'date' => $status === TaskStatusEnum::DRAFT
                ? $now->copy()->addDay()->toDateString()
                : $now->toDateString(),
            'execution_time' => $now->format('H:i:s'),
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444200,
            'longitude' => 31.2357120,
            'location_name' => 'ShelfSpot Demo Store - Downtown Cairo',
            'address' => 'Tahrir Square, Downtown Cairo',
            'total_price' => $service->price,
            'status' => $status,
            'created_by' => $owner->id,
            'payment_status' => $status === TaskStatusEnum::DRAFT
                ? TaskPaymentStatusEnum::PENDING
                : TaskPaymentStatusEnum::CHARGED,
            'charged_at' => $status === TaskStatusEnum::DRAFT ? null : $now,
            'assigned_worker_id' => $worker?->id,
            'expires_at' => $now->copy()->addDay(),
            'accepted_at' => $isAssigned ? $now->copy()->subMinutes(45) : null,
            'start_deadline_at' => $isAssigned ? $now->copy()->subMinutes(30) : null,
            'started_at' => $isAssigned ? $now->copy()->subMinutes(25) : null,
            'expected_completion_at' => $isInProgress ? $now->copy()->addMinutes(35) : null,
            'completed_at' => $isInReview ? $now->copy()->subMinutes(5) : null,
            'auto_accept_at' => $isInReview ? $now->copy()->addDays(3) : null,
        ])->save();

        $taskService = TaskService::query()->updateOrCreate(
            [
                'task_id' => $task->id,
                'service_id' => $service->id,
            ],
            [
                'execution_instructions' => 'Execute the demo catalog task using the listed products.',
                'unit_price' => $service->price,
                'status' => $serviceStatus,
                'sort_order' => 0,
            ],
        );

        foreach ($products as $product) {
            TaskServiceProduct::query()->updateOrCreate(
                [
                    'task_service_id' => $taskService->id,
                    'product_id' => $product->id,
                ],
                ['product_details' => ['seeded_for_task' => $key]],
            );
        }

        TaskStatusHistory::query()->where('task_id', $task->id)->delete();
        TaskWorkerAssignment::query()->where('task_id', $task->id)->delete();

        foreach ($this->statusHistoryFor($status) as [$fromStatus, $toStatus]) {
            TaskStatusHistory::query()->create([
                'task_id' => $task->id,
                'from_status' => $fromStatus?->value,
                'to_status' => $toStatus->value,
                'changed_by' => $worker?->user_id,
            ]);
        }

        if ($worker !== null) {
            TaskWorkerAssignment::query()->create([
                'task_id' => $task->id,
                'worker_id' => $worker->id,
                'assignment_type' => TaskWorkerAssignmentTypeEnum::INITIAL,
                'assigned_by' => $worker->user_id,
                'assigned_at' => $now->copy()->subMinutes(45),
            ]);
        }
    }

    private function statusHistoryFor(TaskStatusEnum $status): array
    {
        return match ($status) {
            TaskStatusEnum::DRAFT => [[null, TaskStatusEnum::DRAFT]],
            TaskStatusEnum::PENDING => [
                [TaskStatusEnum::DRAFT, TaskStatusEnum::PENDING],
            ],
            TaskStatusEnum::IN_PROGRESS => [
                [TaskStatusEnum::DRAFT, TaskStatusEnum::PENDING],
                [TaskStatusEnum::PENDING, TaskStatusEnum::STARTED],
                [TaskStatusEnum::STARTED, TaskStatusEnum::IN_PROGRESS],
            ],
            TaskStatusEnum::COMPLETED => [
                [TaskStatusEnum::DRAFT, TaskStatusEnum::PENDING],
                [TaskStatusEnum::PENDING, TaskStatusEnum::STARTED],
                [TaskStatusEnum::STARTED, TaskStatusEnum::IN_PROGRESS],
                [TaskStatusEnum::IN_PROGRESS, TaskStatusEnum::COMPLETED],
            ],
            default => [],
        };
    }

    private function saveTranslation(object $model, array $names, ?string $arabicDescription = null, ?string $englishDescription = null): void
    {
        foreach ($names as $locale => $name) {
            $translation = $model->translateOrNew($locale);
            $translation->name = $name;

            if ($arabicDescription !== null && $englishDescription !== null) {
                $translation->description = $locale === 'ar' ? $arabicDescription : $englishDescription;
            }

            $translation->save();
        }
    }
}
