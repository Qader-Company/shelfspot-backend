<?php

namespace App\Modules\V1\Coupons\Application\UseCases;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Coupons\Domain\Models\WalletCoupon;
use App\Modules\V1\Coupons\Domain\Models\WalletCouponRedemption;
use App\Modules\V1\Coupons\Domain\Repositories\CouponRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedeemWalletCouponUseCase
{
    public function __construct(
        private readonly CompaniesWalletRepositoryInterface $walletRepository,
        private readonly CouponRepositoryInterface $couponRepository,
    ) {
    }

    public function execute(string $code, int $companyId, ?int $performedBy = null): CompanyWalletTransaction
    {
        if ($companyId === null) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.invalid'),
            ]);
        }

        return DB::transaction(function () use ($code, $companyId, $performedBy) {

            $coupon = $this->ensureCodeRedeemable($code, $companyId);

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
                'redeemed_by' => $performedBy,
                'redeemed_at' => now(),
            ]);

            $coupon->increment('redemptions_count');

            return $this->walletRepository->createTransaction([
                'company_id' => $companyId,
                'amount' => $coupon->amount,
                'description' => __('company.wallet.coupons.redemption_description', ['code' => $coupon->code]),
                'performed_by' => $performedBy,
                'reference_type' => $redemption->getMorphClass(),
                'reference_id' => $redemption->id,
            ], CompanyWalletTransactionTypeEnum::COUPON_REDEMPTION);
        });
    }

    private function ensureCodeRedeemable($code, int $companyId): WalletCoupon
    {
        /** @var WalletCoupon $coupon */
        $coupon = $this->couponRepository->query()
            ->where('code', strtoupper($code))
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->where('expires_at',null)
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function (Builder $query) use ($companyId) {
                $query->where('assigned_company_id',null)
                    ->orWhere('assigned_company_id', $companyId);
            })
            ->lockForUpdate()
            ->firstOrFail();

        if (! $coupon->hasRemainingRedemptions()) {
            throw ValidationException::withMessages([
                'code' => __('company.wallet.coupons.max_redemptions_reached'),
            ]);
        }
        return $coupon;
    }
}
