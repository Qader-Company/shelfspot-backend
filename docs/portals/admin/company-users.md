# Admin Company Users API

ShelfSpot administrators manage users of one selected company through tenant-scoped endpoints under `/api/v1/admin/companies/{company}/users`. The `{company}` path value is authoritative: every user lookup is constrained to that company.

## Endpoints and permissions

| Method | Endpoint | Permission |
|---|---|---|
| `GET` | `/api/v1/admin/companies/{company}/roles` | `view_company_user` |
| `GET` | `/api/v1/admin/companies/{company}/users` | `view_company_user` |
| `POST` | `/api/v1/admin/companies/{company}/users` | `create_company_user` |
| `GET` | `/api/v1/admin/companies/{company}/users/{user}` | `view_company_user` |
| `PATCH` | `/api/v1/admin/companies/{company}/users/{user}` | `edit_company_user` |
| `POST` | `/api/v1/admin/companies/{company}/users/{user}/reset-password` | `reset_company_user_password` |
| `DELETE` | `/api/v1/admin/companies/{company}/users/{user}` | `delete_company_user` |

The user list accepts `search`, `is_active`, and `role` query filters. The roles endpoint returns the roles belonging to the selected company for the role filter and edit form.

## Create

The create endpoint accepts `name`, `email`, `password`, `is_active`, and `roles`. The user is always linked to the company selected in the URL. Supplied role names must belong to that company; the protected `owner` role cannot be assigned.

```json
{
  "name": "Branch Manager",
  "email": "manager@example.com",
  "password": "secure-password",
  "is_active": true,
  "roles": ["manager"]
}
```

## Update

The update endpoint accepts any subset of `name`, `email`, `is_active`, and `roles`. Role names must belong to the selected company and the company portal. Changing an email or deactivating an account revokes its existing access tokens and sessions.

```json
{
  "name": "Branch Manager",
  "email": "manager@example.com",
  "is_active": true,
  "roles": ["manager"]
}
```

The protected company owner can have their name or email edited, but cannot be deactivated or have the protected owner role changed through this API.

## Reset password

Password reset is an administrator-authorized direct reset and does not use the public OTP flow. It revokes all existing access tokens, sessions, and outstanding password-reset tokens for the user.

```json
{
  "password": "new-secure-password",
  "password_confirmation": "new-secure-password"
}
```

The password must contain at least eight characters. Reset is allowed for the company owner because it does not remove ownership or disable the account.

## Delete

Deletion permanently removes the company-user account because users are not soft-deletable in the current schema. The protected company owner cannot be deleted.
