# Company Stores API

Company store endpoints are tenant-scoped under `/api/v1/company/stores`. A store has a single `name`, a company-unique string `number`, `latitude`, `longitude`, `address`, and an optional `is_active` flag that defaults to `true`.

## Endpoints and permissions

| Method | Endpoint | Permission |
|---|---|---|
| `GET` | `/api/v1/company/stores` | `view_store` |
| `GET` | `/api/v1/company/stores/options` | `view_store` or `create_task` |
| `GET` | `/api/v1/company/stores/{id}` | `view_store` |
| `POST` | `/api/v1/company/stores` | `create_store` |
| `PUT/PATCH` | `/api/v1/company/stores/{id}` | `edit_store` |
| `DELETE` | `/api/v1/company/stores/{id}` | `delete_store` |
| `GET` | `/api/v1/company/stores/trash` | `view_store` |
| `POST` | `/api/v1/company/stores/trash/{id}/restore` | `edit_store` |
| `DELETE` | `/api/v1/company/stores/trash/{id}` | `delete_store` |

The collection endpoint accepts `search` and `active` query filters. `search` matches the name, number, or address. The options endpoint returns active, non-deleted stores ordered by name and number for the task form.

## Create request

```json
{
  "name": "Main Branch",
  "number": "BR-001",
  "latitude": 25.2854,
  "longitude": 51.531,
  "address": "Doha",
  "is_active": true
}
```

All fields except `is_active` are required. Latitude must be between `-90` and `90`, longitude between `-180` and `180`, and the number must be unique within the current company. Update accepts the same fields optionally.

Tasks accept `store_id` rather than manual location data. When a task is created or its store changes, the backend copies the store data into the task response snapshot. Existing task history therefore keeps its location details if the store is later edited, disabled, or deleted.
