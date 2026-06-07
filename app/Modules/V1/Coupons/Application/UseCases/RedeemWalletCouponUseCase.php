<?php

namespace App\Modules\V1\Coupons\Application\UseCases;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Coupons\Domain\Models\WalletCoupon;
use App\Modules\V1\Coupons\Domain\Models\WalletCouponRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedeemWalletCouponUseCase
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly CompaniesWalletRepositoryInterface $walletRepository,
    ) {
    }

    public function execute(string $code): CompanyWalletTransaction
    {
        $companyId = $this->tenantContext->getCompanyId();

        if ($companyId === null) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.invalid'),
            ]);
        }

        return DB::transaction(function () use ($code, $companyId) {
            $coupon = WalletCoupon::query()
                ->where('code', strtoupper($code))
                ->lockForUpdate()
                ->first();

            $this->ensureRedeemable($coupon, $companyId);

            $alreadyRedeemed = WalletCouponRedemption::query()
                ->where('wallet_coupon_id', $coupon->id)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->exists();

            if ($alreadyRedeemed) {
                throw ValidationException::withMessages([
                    'code' => __('company.wallet.coupons.already_redeemed'),
                ]);
            }

            $redemption = WalletCouponRedemption::create([
                'wallet_coupon_id' => $coupon->id,
                'company_id' => $companyId,
                'amount' => $coupon->amount,
                'redeemed_by' => auth()->id(),
                'redeemed_at' => now(),
            ]);

            $coupon->increment('redemptions_count');

            return $this->walletRepository->createTransaction([
                'company_id' => $companyId,
                'amount' => $coupon->amount,
                'description' => __('company.wallet.coupons.redemption_description', ['code' => $coupon->code]),
                'performed_by' => auth()->id(),
                'reference_type' => $redemption->getMorphClass(),
                'reference_id' => $redemption->id,
            ], CompanyWalletTransactionTypeEnum::COUPON_REDEMPTION);
        });
    }

    private function ensureRedeemable(?WalletCoupon $coupon, int $companyId): void
    {
        if ($coupon === null) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.invalid'),
            ]);
        }

        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.inactive'),
            ]);
        }

        if ($coupon->isExpired()) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.expired'),
            ]);
        }

        if (! $coupon->isAssignedToCompany($companyId)) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.assigned_to_another_company'),
            ]);
        }

        if (! $coupon->hasRemainingRedemptions()) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.max_redemptions_reached'),
            ]);
        }
    }
}
