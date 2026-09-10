# Flow: Admin Withdrawal Processing

## Description

ShelfSpot admins review worker withdrawal requests and record the result of the external transfer. The API does not transfer money through a bank or wallet provider.

## Permissions

- `view_withdrawal`: list and show withdrawal requests.
- `process_withdrawal`: mark a pending request as paid or reject it.

## Endpoints

### List withdrawal requests

- **Method:** GET
- **URL:** `/api/v1/admin/withdrawals`
- **Optional filters:** `search`, `worker_id`, `status`, `method`, `date_from`, `date_to`.
- The response contains aggregate `summary` values for the selected worker/method/date period and a paginated `withdrawals` collection.

### Show withdrawal request

- **Method:** GET
- **URL:** `/api/v1/admin/withdrawals/{withdrawal}`

### Approve and mark paid

- **Method:** POST
- **URL:** `/api/v1/admin/withdrawals/{withdrawal}/approve`

`approve` is the final payment action. It changes a `pending` request directly to `paid` and stores the acting admin and payment timestamp. There is no separate `mark-paid` endpoint.

### Reject withdrawal request

- **Method:** POST
- **URL:** `/api/v1/admin/withdrawals/{withdrawal}/reject`

```json
{
  "reason": "Invalid payout details"
}
```

Rejecting a pending request changes it to `rejected` and returns the reserved amount to the worker wallet exactly once.

## State rules

- Valid transitions are `pending -> paid` and `pending -> rejected`.
- Repeating the same final action is idempotent.
- A paid request cannot be rejected, and a rejected request cannot be paid.
- Withdrawal requests are financial records and do not have delete endpoints.
