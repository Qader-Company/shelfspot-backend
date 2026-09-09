# Admin Companies API

## Company details statistics

`GET /api/v1/admin/companies/{company}` returns the company details, users, latest tasks, and statistics.

Pass `date_from` and/or `date_to` to recalculate the statistics for records created in the selected period:

```http
GET /api/v1/admin/companies/5?date_from=2026-08-01&date_to=2026-08-31
```

The date range applies to `total_requests_count`, `completed_requests_count`, `pending_requests_count`, `total_spending`, and `total_products_count`. It does not filter `users` or `latest_tasks`. If no dates are supplied, the endpoint returns the all-time statistics.

`date_to` must be on or after `date_from` when both are supplied.
