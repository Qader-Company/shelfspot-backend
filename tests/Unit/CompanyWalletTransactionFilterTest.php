<?php

namespace Tests\Unit;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyWalletTransactionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_wallet_transactions_by_type_and_created_at_date_range(): void
    {
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);

        $included = $this->createTransaction(
            $company->id,
            CompanyWalletTransactionTypeEnum::TASK_PAYMENT,
            '2026-07-15 12:00:00'
        );
        $outsideDateRange = $this->createTransaction(
            $company->id,
            CompanyWalletTransactionTypeEnum::TASK_PAYMENT,
            '2026-07-20 12:00:00'
        );
        $differentType = $this->createTransaction(
            $company->id,
            CompanyWalletTransactionTypeEnum::TASK_REFUND,
            '2026-07-15 12:00:00'
        );

        $transactionIds = CompanyWalletTransaction::query()
            ->filter([
                'type' => CompanyWalletTransactionTypeEnum::TASK_PAYMENT->value,
                'date_from' => '2026-07-15',
                'date_to' => '2026-07-15',
            ])
            ->pluck('id')
            ->all();

        $this->assertSame([$included->id], $transactionIds);
        $this->assertNotContains($outsideDateRange->id, $transactionIds);
        $this->assertNotContains($differentType->id, $transactionIds);
    }

    private function createTransaction(
        int $companyId,
        CompanyWalletTransactionTypeEnum $type,
        string $createdAt
    ): CompanyWalletTransaction {
        $transaction = CompanyWalletTransaction::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'type' => $type,
            'amount' => 100,
            'balance_after' => 100,
        ]);

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $transaction;
    }
}
