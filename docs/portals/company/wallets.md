# Flow: Company Wallet Overview

## Description
Company Wallet Overview documents how a company user views the current company wallet balance, lists wallet transactions, filters transactions by type, and opens one transaction by ID.

## Business Goal
Give the company finance/admin UI a reliable source of wallet balance and transaction history so task payments, refunds, admin grants, coupon redemptions, and adjustments can be audited.

## Module Overview
This flow belongs to the company portal Wallets module. Endpoints are under `/api/v1/company/wallets`, require a company Bearer token, resolve tenant data through `X-Company-Slug`, and require `view_wallet` permission.

## Prerequisites
- Client has a valid company access token with company portal access ability.
- Client sends `X-Authorization` with the platform API key.
- Client sends `X-Company-Slug` for tenant context.
- The acting company user has `view_wallet` permission.
- Optional transaction type filters should use supported wallet transaction types such as `coupon_redemption`, `admin_grant`, `task_payment`, `task_refund`, or `adjustment`.

## Walkthrough
1. Call `List Wallet Transactions` to show the current wallet balance and paginated transaction history.
2. Optionally pass `type`, `date_from`, and `date_to` as query filters to narrow transaction history.
3. Read each transaction `id`, `type`, `amount`, `balance_after`, `performed_by`, and dates.
4. Call `Show Wallet Transaction` when the UI needs to open one transaction detail record.

## Endpoint: List Wallet Transactions
- **Method:** GET
- **URL:** /api/v1/company/wallets
- **Auth:** Bearer
- **Purpose:** Return current company wallet balance and paginated wallet transactions.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "balance": 1250.75,
    "transactions": {
      "data": [
        {
          "id": 101,
          "type": "coupon_redemption",
          "type_label": "Coupon redemption",
          "amount": "250.00",
          "balance_after": "1250.75",
          "performed_by": {
            "id": 15,
            "name": "Company Owner",
            "email": "owner@example.com"
          },
          "description": "Coupon redeemed: WELCOME250",
          "created_at": "2026-07-04",
          "updated_at": "2026-07-04"
        }
      ],
      "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 1
      }
    }
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

### Examples
#### Example: List coupon redemption transactions
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "balance": 1250.75,
    "transactions": {
      "data": [
        {
          "id": 101,
          "type": "coupon_redemption",
          "type_label": "Coupon redemption",
          "amount": "250.00",
          "balance_after": "1250.75",
          "performed_by": {
            "id": 15,
            "name": "Company Owner",
            "email": "owner@example.com"
          },
          "description": "Coupon redeemed: WELCOME250",
          "created_at": "2026-07-04",
          "updated_at": "2026-07-04"
        }
      ],
      "meta": { "current_page": 1, "per_page": 15, "total": 1 }
    }
  }
}
```

### Notes
The endpoint accepts the following optional query filters for transaction history:

- `type`: one of the supported wallet transaction types.
- `date_from`: include transactions created on or after this date.
- `date_to`: include transactions created on or before this date; it must not be before `date_from`.

The `balance` value is always computed from the latest wallet transaction for the current company, independently of the selected filters.

## Endpoint: Show Wallet Transaction
- **Method:** GET
- **URL:** /api/v1/company/wallets/{id}
- **Auth:** Bearer
- **Purpose:** Return one wallet transaction by ID for the current company.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "data": {
    "id": 101,
    "type": "coupon_redemption",
    "type_label": "Coupon redemption",
    "amount": "250.00",
    "balance_after": "1250.75",
    "performed_by": {
      "id": 15,
      "name": "Company Owner",
      "email": "owner@example.com"
    },
    "description": "Coupon redeemed: WELCOME250",
    "created_at": "2026-07-04",
    "updated_at": "2026-07-04"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **404 — transaction not found**
```json
{ "success": false, "message": "Not found." }
```

### Examples
#### Example: Show wallet transaction detail
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "data": {
    "id": 101,
    "type": "coupon_redemption",
    "type_label": "Coupon redemption",
    "amount": "250.00",
    "balance_after": "1250.75",
    "performed_by": {
      "id": 15,
      "name": "Company Owner",
      "email": "owner@example.com"
    },
    "description": "Coupon redeemed: WELCOME250",
    "created_at": "2026-07-04",
    "updated_at": "2026-07-04"
  }
}
```

### Notes
The `{id}` path parameter is the wallet transaction ID. The transaction is resolved through company wallet scoping.

## Branch: Filter transaction history
**Condition:** The company user wants to review only one wallet activity type.

### Case: Filter by transaction type
**When:** The UI sends a transaction `type` query value such as `task_payment` or `coupon_redemption`.
**Explanation:** The list endpoint returns the same balance summary, but the paginated `transactions` collection is narrowed by the selected type.

#### Endpoint: List Wallet Transactions
- **Method:** GET
- **URL:** /api/v1/company/wallets

---
# Flow: Company Wallet Recharge

## Description
Company Wallet Recharge documents how a company user with recharge permission adds wallet credit manually and receives the updated balance plus the created wallet transaction.

## Business Goal
Allow authorized company users to add funds to the company wallet so task creation and task payment flows can proceed when enough balance exists.

## Module Overview
This flow belongs to the company portal Wallets module. The recharge endpoint creates a company wallet transaction with type `admin_grant`, stores the acting user as `performed_by`, and returns the balance after recharge.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `recharge_wallet` permission.
- Recharge amount must be numeric and at least `1`.

## Walkthrough
1. Company user opens the recharge form.
2. Client submits an amount and optional description to `Recharge Wallet`.
3. Backend creates a wallet transaction and recalculates `balance_after`.
4. Client updates the displayed wallet balance from the response.
5. Client can call `List Wallet Transactions` to refresh the history table.

## Endpoint: Recharge Wallet
- **Method:** POST
- **URL:** /api/v1/company/wallets/recharge
- **Auth:** Bearer
- **Purpose:** Add funds to the current company wallet.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "amount": "number (required, min:1)",
  "description": "string (optional nullable, max:1000)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Wallet transaction completed successfully.",
  "data": {
    "balance": 1500.75,
    "transaction": {
      "id": 102,
      "type": "admin_grant",
      "type_label": "Admin grant",
      "amount": "250.00",
      "balance_after": "1500.75",
      "performed_by": {
        "id": 15,
        "name": "Company Owner",
        "email": "owner@example.com"
      },
      "description": "Manual recharge from company portal",
      "created_at": "2026-07-04",
      "updated_at": "2026-07-04"
    }
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The amount field is required.",
  "errors": {
    "amount": ["The amount field is required."],
    "description": ["The description field must not be greater than 1000 characters."]
  }
}
```

### Examples
#### Example: Recharge wallet by amount
Request:
```json
{
  "amount": 250,
  "description": "Manual recharge from company portal"
}
```
Response:
```json
{
  "success": true,
  "message": "Wallet transaction completed successfully.",
  "data": {
    "balance": 1500.75,
    "transaction": {
      "id": 102,
      "type": "admin_grant",
      "type_label": "Admin grant",
      "amount": "250.00",
      "balance_after": "1500.75",
      "description": "Manual recharge from company portal",
      "created_at": "2026-07-04",
      "updated_at": "2026-07-04"
    }
  }
}
```

### Notes
If `description` is omitted, the backend uses the default manual recharge description translation.

## Branch: Recharge succeeds
**Condition:** The recharge request is valid and the wallet transaction is created.

### Case: Update wallet balance in UI
**When:** The response includes a new `balance` and `transaction`.
**Explanation:** Replace the visible wallet balance with `data.balance`, prepend the returned transaction to the transaction list, and keep the user on the wallet screen.

#### Endpoint: Recharge Wallet
- **Method:** POST
- **URL:** /api/v1/company/wallets/recharge

---
# Flow: Company Wallet Coupon Redemption

## Description
Company Wallet Coupon Redemption documents how a company user redeems a wallet coupon code and receives the resulting wallet transaction and updated balance.

## Business Goal
Allow companies to add promotional or assigned coupon credit to their wallet without manual recharge.

## Module Overview
This flow belongs to the company portal Wallets and Coupons modules. The coupon redemption endpoint validates coupon code status, expiry, assignment to the current company, redemption limits, and previous redemption by the same company before creating a `coupon_redemption` wallet transaction.

## Prerequisites
- Client has a valid company Bearer token.
- Client sends `X-Authorization` and `X-Company-Slug`.
- The acting company user has `recharge_wallet` permission.
- Coupon code is available to the current company, active, unexpired, and has remaining redemptions.
- The current company has not already redeemed the coupon.

## Walkthrough
1. Company user enters a coupon code.
2. Client submits the code to `Redeem Wallet Coupon`.
3. Backend validates coupon eligibility for the current company.
4. Backend creates a coupon redemption record and wallet transaction.
5. Client updates the displayed wallet balance and transaction history from the response.

## Endpoint: Redeem Wallet Coupon
- **Method:** POST
- **URL:** /api/v1/company/wallets/coupons/redeem
- **Auth:** Bearer
- **Purpose:** Redeem a wallet coupon code for the current company.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
X-Company-Slug: {{company_slug}}
```

### Request Body
```json
{
  "code": "string (required, max:100)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Coupon redeemed successfully.",
  "data": {
    "balance": 1750.75,
    "transaction": {
      "id": 103,
      "type": "coupon_redemption",
      "type_label": "Coupon redemption",
      "amount": "250.00",
      "balance_after": "1750.75",
      "performed_by": {
        "id": 15,
        "name": "Company Owner",
        "email": "owner@example.com"
      },
      "description": "Coupon redeemed: WELCOME250",
      "created_at": "2026-07-04",
      "updated_at": "2026-07-04"
    }
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — missing permission**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The code field is required.",
  "errors": {
    "code": ["The code field is required."]
  }
}
```

- **422 — coupon not redeemable**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": ["The coupon is invalid, expired, unavailable, already redeemed, or has reached its redemption limit."]
  }
}
```

### Examples
#### Example: Redeem coupon code
Request:
```json
{
  "code": "WELCOME250"
}
```
Response:
```json
{
  "success": true,
  "message": "Coupon redeemed successfully.",
  "data": {
    "balance": 1750.75,
    "transaction": {
      "id": 103,
      "type": "coupon_redemption",
      "type_label": "Coupon redemption",
      "amount": "250.00",
      "balance_after": "1750.75",
      "description": "Coupon redeemed: WELCOME250",
      "created_at": "2026-07-04",
      "updated_at": "2026-07-04"
    }
  }
}
```

### Notes
The backend uppercases coupon codes before lookup. A coupon can be global or assigned to the current company, but each company can redeem the same coupon only once.

## Branch: Coupon rejected
**Condition:** The submitted coupon cannot be redeemed by the current company.

### Case: Show coupon validation error
**When:** The response contains a `code` validation error.
**Explanation:** Keep the wallet balance unchanged, show the validation message near the coupon input, and let the user try a different code.

#### Endpoint: Redeem Wallet Coupon
- **Method:** POST
- **URL:** /api/v1/company/wallets/coupons/redeem
