# Frontend permissions guide

This document is the frontend contract for ShelfSpot permissions. Permission names are stable machine-readable values; the localized `label` is display-only.

## Response contract

Admin and company users receive these arrays inside the `user` object returned by login and inside the profile response:

```json
{
  "permissions": [
    {
      "id": 1,
      "name": "view_dashboard",
      "label": "View dashboard",
      "portal": "admin"
    }
  ],
  "available_permissions": [
    {
      "id": 2,
      "name": "edit_platform_settings",
      "label": "Edit platform settings",
      "portal": "admin"
    }
  ]
}
```

- `permissions` contains permissions the authenticated user **has** through their roles.
- `available_permissions` contains permissions the user **does not have** in the current portal.
- Worker responses do not contain either array because worker routes do not use this role-permission catalog.
- Company permissions are scoped to the user's company. Permissions from a role belonging to another company must never be used.
- Use `name`, not `id` or `label`, for frontend authorization checks. Database IDs can differ between environments, and labels change with the response locale.

## Frontend usage

Build a set once when authentication/profile data is received:

```ts
type Permission = {
  id: number;
  name: string;
  label: string;
  portal: 'admin' | 'company';
};

const grantedPermissions = new Set(
  user.permissions.map((permission: Permission) => permission.name),
);

export const can = (permission: string): boolean =>
  grantedPermissions.has(permission);
```

Examples:

```tsx
{can('create_company') && <CreateCompanyButton />}
{can('edit_product') && <EditProductButton />}
```

Use `permissions` to show or enable protected navigation, screens, and actions. Use `available_permissions` only in access-management UI (for example, explaining missing access); do **not** interpret it as granted access. A hidden button is not a security boundary—the API remains authoritative and can return HTTP `403`.

Permission names overlap between portals. For example, `view_role` exists in both admin and company portals, but it controls a different URL namespace. The authenticated portal must therefore be considered alongside the permission name.

## Admin portal permissions

These permissions apply to `/api/v1/admin/*` and the ShelfSpot administration UI.

| Permission | Frontend capability |
|---|---|
| `view_dashboard` | Open and load the admin dashboard. |
| `view_platform_settings` | Show the platform-settings screen/read action. See the enforcement note below. |
| `edit_platform_settings` | Show controls that update platform settings. |
| `view_company` | List companies, open company details, and view deleted companies. |
| `create_company` | Show the create-company action and form. |
| `edit_company` | Edit companies and restore deleted companies. |
| `delete_company` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete companies. |
| `manage_company_catalog` | View and manage a selected company's brands, sub-brands, categories, sub-categories, and products from the admin portal. This is a combined catalog permission, not separate CRUD permissions. |
| `view_service` | List services and open service details. |
| `create_service` | Create a platform service. |
| `edit_service` | Edit a platform service. |
| `delete_service` | Delete a platform service. |
| `view_worker` | List workers, open worker details, and view deleted workers. |
| `create_worker` | Create a worker account. |
| `edit_worker` | Edit workers and restore deleted workers. |
| `delete_worker` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete workers. |
| `view_task` | List tasks, open task details, view task history, and view deleted tasks. |
| `delete_task` | Delete an admin-side task record. |
| `reassign_task` | Assign/reassign a task, list eligible workers for reassignment, and reopen a task. |
| `view_payment` | List payments and open payment details. There are currently no create/edit/delete payment permissions. |
| `view_wallet_coupon` | List wallet coupons and open coupon details. |
| `create_wallet_coupon` | Create a wallet coupon. |
| `edit_wallet_coupon` | Edit a wallet coupon. |
| `delete_wallet_coupon` | Delete a wallet coupon. |
| `view_role` | List permission definitions and roles in admin access control. |
| `create_role` | Create an admin role and assign permissions to it. |
| `edit_role` | Rename an admin role or change its permissions. |
| `delete_role` | Delete a non-protected admin role. |
| `view_admin` | List ShelfSpot admin accounts. |
| `create_admin` | Create a ShelfSpot admin and assign roles. |
| `edit_admin` | Edit a ShelfSpot admin, status, or role assignments. |
| `delete_admin` | Delete a non-protected ShelfSpot admin. |

## Company portal permissions

These permissions apply to `/api/v1/company/*` and are evaluated for the authenticated user's company.

| Permission | Frontend capability |
|---|---|
| `view_company` | Show company details. See the enforcement note below. |
| `edit_company` | Show controls for updating company details. See the enforcement note below. |
| `view_brand` | List brands, open brand details, use brand filter options, and view deleted brands. |
| `create_brand` | Create brands and import brand data where supported. |
| `edit_brand` | Edit brands, restore deleted brands, and bulk restore brands. |
| `delete_brand` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete brands. |
| `view_sub_brand` | List sub-brands, open details, download templates/exports, and view deleted sub-brands. |
| `create_sub_brand` | Create sub-brands and import sub-brand data. |
| `edit_sub_brand` | Edit and restore sub-brands. |
| `delete_sub_brand` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete sub-brands. |
| `view_category` | List categories, open details, use category options, and view deleted categories. |
| `create_category` | Create categories and import category data where supported. |
| `edit_category` | Edit and restore categories. |
| `delete_category` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete categories. |
| `view_sub_category` | List sub-categories, open details, download templates/exports, and view deleted sub-categories. |
| `create_sub_category` | Create sub-categories and import sub-category data. |
| `edit_sub_category` | Edit and restore sub-categories. |
| `delete_sub_category` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete sub-categories. |
| `view_product` | List products, open details, load filter options, download templates/exports, and view deleted products. |
| `create_product` | Create products and import product data. |
| `edit_product` | Edit and restore products. |
| `delete_product` | Soft-delete, bulk-delete, permanently delete, or bulk permanently delete products. |
| `view_service` | Show the platform services available to a company. See the enforcement note below. |
| `view_wallet` | Open the company wallet, list wallet activity, and view transaction details. |
| `recharge_wallet` | Recharge the wallet or redeem a wallet coupon. |
| `view_task` | List tasks, open task details/history/review data, view deleted tasks, and use view-level task decisions such as accept/reject where exposed. |
| `create_task` | Pay for/submit a new task. See the enforcement note for the initial draft creation endpoint. |
| `edit_task` | Edit, cancel, or restore a task. |
| `delete_task` | Soft-delete or permanently delete a task. |
| `view_role` | List permission definitions and roles for the current company. |
| `create_role` | Create a company role and assign permissions to it. |
| `edit_role` | Rename a company role or change its permissions. |
| `delete_role` | Delete a non-protected company role. |
| `view_admin` | List admin users belonging to the current company. |
| `create_admin` | Create a company admin and assign company roles. |
| `edit_admin` | Edit a company admin, status, or role assignments. |
| `delete_admin` | Delete a non-owner company admin. |

## Current enforcement notes

The tables describe the intended frontend capability and the permission catalog. The following read/create endpoints are currently authenticated but do not have matching permission middleware at the route level:

1. Admin `GET /api/v1/admin/platform-settings` does not currently enforce `view_platform_settings`; updates do enforce `edit_platform_settings`.
2. Company `GET /api/v1/company/profile/company` and `PUT/PATCH /api/v1/company/profile/company` do not currently enforce `view_company` and `edit_company` at route level.
3. Company `GET /api/v1/company/services` and `GET /api/v1/company/services/{id}` do not currently enforce `view_service`.
4. Company `POST /api/v1/company/tasks` does not currently enforce `create_task`; the later pay action does.

Until backend enforcement is aligned, the frontend should still use the documented permission for visibility, but it must not assume that route middleware behavior defines the product rule.

## Access-management rules

- The protected `super_admin` role has all admin permissions and cannot be manually created, modified, assigned as an ordinary role, or deleted.
- The protected company `owner` role has all company permissions and cannot be manually created, modified, or deleted.
- Role forms submit permission **names**, for example:

```json
{
  "name": "catalog_manager",
  "permissions": [
    "view_brand",
    "create_brand",
    "edit_brand",
    "view_product"
  ]
}
```

- Refresh the stored user/session data from login or profile after role assignments change. Do not permanently cache permission decisions in the browser.
- Render the backend-provided `label` in access-management screens so Arabic/English localization remains controlled by the API locale.

## Recommended frontend constants

Keep portal-specific constants so identical names are not accidentally used in the wrong portal:

```ts
export const ADMIN_PERMISSION = {
  VIEW_DASHBOARD: 'view_dashboard',
  MANAGE_COMPANY_CATALOG: 'manage_company_catalog',
  VIEW_ROLE: 'view_role',
  VIEW_ADMIN: 'view_admin',
} as const;

export const COMPANY_PERMISSION = {
  VIEW_BRAND: 'view_brand',
  CREATE_PRODUCT: 'create_product',
  VIEW_TASK: 'view_task',
  RECHARGE_WALLET: 'recharge_wallet',
} as const;
```

The backend enum files remain the source of truth for the complete list. When an enum changes, update the frontend constants and this guide in the same release.
