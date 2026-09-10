# Flow: Worker Wallet and Withdrawals

## Description

The worker wallet records the worker's 50% share when a charged task becomes accepted. A withdrawal request reserves its amount immediately, so the available balance cannot be spent twice.

## Authentication

All endpoints require a worker Bearer token and the platform `X-Authorization` header.

The client can populate selectors from `/api/v1/enums/withdrawal-methods`, `/api/v1/enums/withdrawal-statuses`, and `/api/v1/enums/worker-wallet-transaction-types`.

## Endpoints

### Wallet overview and transaction history

- **Method:** GET
- **URL:** `/api/v1/worker/wallet`
- **Optional filters:** `type`, `date_from`, `date_to`.
- **Types:** `task_earning`, `withdrawal`, `withdrawal_refund`, `adjustment`.
- **Response fields:** `statistics` contains `available_balance`, `total_earned`, `pending_withdrawals`, and `total_withdrawn`; `transactions` contains the paginated unified ledger.

Task earnings, withdrawals, and withdrawal refunds all appear in the same transaction collection. A withdrawal transaction embeds its current status, method, payout details, processing timestamps, and rejection reason.

### Create withdrawal request

- **Method:** POST
- **URL:** `/api/v1/worker/wallet/withdrawals`

Bank account request:

```json
{
  "amount": 1200,
  "method": "bank_account",
  "iban": "SA0380000000608010167519"
}
```

Mobile wallet request:

```json
{
  "amount": 500,
  "method": "wallet",
  "wallet_number": "01000000000",
  "wallet_provider": "Vodafone Cash"
}
```

The request starts as `pending`. Its amount is subtracted from `available_balance` in the same database transaction. If the admin rejects it, the amount is returned through a separate wallet ledger transaction.

## Task settlement

Settlement happens exactly once after the company accepts the completed task or after automatic acceptance. The full task amount is split as follows:

- Worker: 50%.
- Platform: the remaining 50%, including any one-cent rounding remainder.

The admin payment resource exposes the immutable settlement snapshot, while the worker sees their share as a `task_earning` transaction. Existing accepted tasks can be settled idempotently after deployment with:

```bash
php artisan wallets:backfill-task-earnings
```
