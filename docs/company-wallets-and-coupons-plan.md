# Company Wallets and Wallet Coupons Plan

## Scope

We will close the company wallets and wallet coupons scope without integrating a payment gateway in this phase. The wallet balance source of truth is the `company_wallet_transactions` ledger, specifically the latest `balance_after` value for the current company.

## Agreed product decisions

- Any authenticated company user can manually recharge/grant wallet credit for now. Fine-grained permissions will be introduced later.
- `once_per_company` is not needed as a configurable coupon option. Wallet coupons are treated as once-per-company by default through redemption constraints.
- Wallet balance is not stored on the company record in this phase. The ledger is enough for now.
- Coupons are only for company wallet credit, not general discounts or payment-gateway coupons.
- Payment gateway, payment sessions, provider webhooks, and external payment reconciliation are out of scope.

## Phase 1: Company wallet foundation

### Goals

- Make company wallet balance calculation deterministic and scoped to the current company.
- Keep the company wallet API usable without a payment gateway.
- Return a reliable current balance alongside paginated transactions.
- Prepare wallet transaction creation for future task debit/refund integration.

### Implementation tasks

1. Fix wallet balance calculation to use the latest ledger transaction `balance_after` instead of adding to the model instance.
2. Support all current company wallet transaction types in the calculator.
3. Add repository helpers for current balance and latest transaction lookup.
4. Keep manual recharge/grant available to authenticated company users.
5. Improve wallet transaction response payloads with ids, raw type values, translated labels, null-safe actor data, and timestamps.
6. Add translations for wallet transaction types and common wallet messages.
7. Add or extend tests for company wallet listing, balance calculation, tenant isolation, and manual recharge.

### Acceptance criteria

- A company user can list wallet transactions and receive a reliable current balance.
- A company user can manually recharge the wallet and get the updated balance.
- Balance calculations use only the current tenant/company ledger.
- No payment gateway code is introduced.

## Phase 2: Wallet coupon admin CRUD foundation

### Goals

- Fix the coupons module naming mismatch and base it on `WalletCoupon`.
- Provide admin CRUD APIs for wallet coupons.
- Remove configurable `once_per_company` from the coupon contract.
- Prepare the coupon model/repository for redemption in a later phase.

### Implementation tasks

1. Update coupon repository contracts and implementation to use `WalletCoupon`.
2. Add wallet coupon casts, relationships, and helper methods.
3. Add admin wallet coupon CRUD requests, resource, controller, and routes.
4. Add filtering/search support for wallet coupons.
5. Remove `once_per_company` from wallet coupon schema and request/resource contracts.
6. Add translations for coupon CRUD and coupon validation/business messages.
7. Add tests for admin coupon list/create/show/update/delete.

### Acceptance criteria

- Admin users can create, list, show, update, and delete wallet coupons.
- Coupon amount, active state, expiry, max redemptions, optional assigned company, creator, and notes are represented consistently.
- Coupon repository no longer references a missing `Coupon` model.
- No coupon redemption endpoint is required in this phase.

## Phase 3: Company coupon redemption

### Goals

- Allow a company user to redeem an active wallet coupon code.
- Credit the company wallet via a ledger transaction of type `coupon_redemption`.
- Enforce expiry, active state, assigned company, max redemptions, and once-per-company redemption.

### Implementation tasks

1. Add a company redeem endpoint under company wallets.
2. Add a redeem request and use case.
3. Lock coupon rows during redemption to enforce `max_redemptions` safely.
4. Create a wallet coupon redemption row and a wallet transaction in one database transaction.
5. Return the updated wallet balance.
6. Add tests for successful redemption and all failure cases.

## Phase 4: Task wallet integration

### Goals

- Use the company wallet ledger for task payments and refunds.
- Keep task debit/refund behavior independent from payment gateway integration.

### Implementation tasks

1. Define where task payment debit happens.
2. Define refund rules for cancelled/rejected/failed task flows.
3. Add optional reference linking between wallet transactions and task/coupon sources if needed.
4. Add tests for sufficient funds, insufficient funds, debit, and refund.
