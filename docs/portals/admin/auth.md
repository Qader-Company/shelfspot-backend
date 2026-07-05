# Flow: Admin Authentication

## Description
Admin Authentication documents how ShelfSpot admins log in, refresh their session tokens, and log out from the admin portal.

## Business Goal
Allow authorized admins to access protected admin portal APIs, keep sessions alive with refresh tokens, and revoke active tokens when signing out.

## Module Overview
This flow belongs to the public Authentication module for the admin portal. Admin login uses the shared `/api/v1/auth/{type}` routes with fixed type `admin`. Successful login returns an admin access token for `/api/v1/admin/*` APIs and a refresh token for session renewal.

## Prerequisites
- `baseUrl` points to the API host.
- Client has a valid platform API key for `X-Authorization`.
- Admin account already exists in the system.
- Admin knows a valid email and password.
- Refresh requires a Bearer token with refresh-token ability.

## Walkthrough
1. Admin submits email and password to `Login Admin`.
2. Backend validates credentials and returns access and refresh tokens.
3. Client stores the access token for protected admin portal calls.
4. Client uses `Refresh Admin Token` when the access token expires.
5. Client calls `Logout Admin` to revoke the active token when the admin signs out.

## Endpoint: Login Admin
- **Method:** POST
- **URL:** /api/v1/auth/admin/login
- **Auth:** none
- **Purpose:** Authenticate an admin user and return admin access and refresh tokens.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
```

### Request Body
```json
{
  "email": "string (required, valid email, max:255)",
  "password": "string (required, min:6)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "access_token": "admin_access_token_value",
    "refresh_token": "admin_refresh_token_value",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "ShelfSpot Admin",
      "email": "admin@shelfspot.com",
      "type": "admin"
    }
  }
}
```

### Failures
- **401 — invalid credentials**
```json
{ "success": false, "message": "Invalid credentials." }
```

- **401 — invalid api key**
```json
{ "success": false, "message": "Invalid API key." }
```

- **422 — validation error**
```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "password": ["The password field must be at least 6 characters."]
  }
}
```

### Examples
#### Example: Normal admin login
Request:
```json
{
  "email": "admin@shelfspot.com",
  "password": "73eQf4b1"
}
```
Response:
```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "access_token": "eyJhbGciOi...adminAccess",
    "refresh_token": "eyJhbGciOi...adminRefresh",
    "token_type": "Bearer"
  }
}
```

### Notes
Use the returned access token with admin routes under `/api/v1/admin/*`. Do not use the refresh token for normal admin API calls.

## Endpoint: Refresh Admin Token
- **Method:** POST
- **URL:** /api/v1/auth/admin/refresh
- **Auth:** Bearer
- **Purpose:** Exchange a valid admin refresh token for a new token pair.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{refresh_token}}
```

### Request Body
```json
{
  "email": "string (required, valid email, max:255)",
  "password": "string (required, min:6)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "access_token": "new_admin_access_token_value",
    "refresh_token": "new_admin_refresh_token_value",
    "token_type": "Bearer"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — wrong token ability**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": ["The email field must be a valid email address."]
  }
}
```

### Examples
#### Example: Refresh admin session
Request:
```json
{
  "email": "admin@shelfspot.com",
  "password": "73eQf4b1"
}
```
Response:
```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "access_token": "eyJhbGciOi...newAdminAccess",
    "refresh_token": "eyJhbGciOi...newAdminRefresh",
    "token_type": "Bearer"
  }
}
```

### Notes
This endpoint requires `Authorization: Bearer {{refresh_token}}` with the refresh-token ability.

## Endpoint: Logout Admin
- **Method:** DELETE
- **URL:** /api/v1/auth/logout
- **Auth:** Bearer
- **Purpose:** Revoke the active admin token/session.

### Headers
```
Accept: application/json
Authorization: Bearer {{admin_access_token}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **429 — too many logout requests**
```json
{ "success": false, "message": "Too many attempts. Please try again later." }
```

### Examples
#### Example: Admin logout
Request:
```json
{}
```
Response:
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

### Notes
Logout is shared across portals and revokes the token used in the request.

## Branch: Admin session state
**Condition:** Depends on whether the current admin access token is valid or expired.

### Case: Active token
**When:** Admin API calls under `/api/v1/admin/*` succeed.
**Explanation:** Continue using the current access token until it expires or the admin logs out.

#### Endpoint: Login Admin
- **Method:** POST
- **URL:** /api/v1/auth/admin/login

### Case: Expired token
**When:** Protected admin API calls return `401 Unauthenticated` and a refresh token exists.
**Explanation:** Call `Refresh Admin Token`, replace the stored access token, and retry the original admin API request.

#### Endpoint: Refresh Admin Token
- **Method:** POST
- **URL:** /api/v1/auth/admin/refresh

---
# Flow: Admin Reset Password

## Description
Admin Reset Password documents how an admin requests a reset OTP, verifies the OTP, receives a reset-password token, and sets a new password.

## Business Goal
Allow an admin who forgot their password to regain access securely by proving ownership of the registered admin email address.

## Module Overview
This flow belongs to the public Authentication module. The admin reset flow uses typed OTP routes with fixed type `admin`, then uses the shared reset-password endpoint with a Bearer reset-password token.

## Prerequisites
- `baseUrl` points to the API host.
- Client has a valid platform API key for `X-Authorization`.
- Admin knows the registered email address.
- Admin can access the email inbox to read OTP.
- New password must be at least 8 characters, mixed case, and confirmed.

## Walkthrough
1. Admin submits their email to `Ask for Admin Reset OTP`.
2. Backend sends a reset-password OTP to the admin email.
3. Admin submits email and OTP to `Verify Admin Reset OTP`.
4. Backend returns a reset-password Bearer token.
5. Admin submits the new password and confirmation to `Reset Admin Password`.

## Endpoint: Ask for Admin Reset OTP
- **Method:** POST
- **URL:** /api/v1/auth/admin/password-reset/send-otp
- **Auth:** none
- **Purpose:** Send a password-reset OTP to the admin email address.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
```

### Request Body
```json
{
  "email": "string (required, valid email, max:255)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "OTP sent successfully.",
  "data": {
    "email": "admin@shelfspot.com"
  }
}
```

### Failures
- **401 — invalid api key**
```json
{ "success": false, "message": "Invalid API key." }
```

- **422 — validation error**
```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": ["The email field must be a valid email address."]
  }
}
```

### Examples
#### Example: Request admin reset OTP
Request:
```json
{
  "email": "admin@shelfspot.com"
}
```
Response:
```json
{
  "success": true,
  "message": "OTP sent successfully.",
  "data": { "email": "admin@shelfspot.com" }
}
```

### Notes
The OTP is used only to prove access to the admin email before password reset.

## Endpoint: Verify Admin Reset OTP
- **Method:** POST
- **URL:** /api/v1/auth/admin/reset-password-verification
- **Auth:** none
- **Purpose:** Verify reset OTP and return a reset-password token.

### Headers
```
Accept: application/json
Content-Type: application/json
X-Authorization: {{api_key}}
```

### Request Body
```json
{
  "email": "string (required, valid email, max:255)",
  "otp": "string (required, exactly 6 digits)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "OTP verified successfully.",
  "data": {
    "token": "reset_password_token_value",
    "token_type": "Bearer"
  }
}
```

### Failures
- **422 — invalid otp**
```json
{
  "message": "The provided OTP is invalid or expired.",
  "errors": {
    "otp": ["The provided OTP is invalid or expired."]
  }
}
```

### Examples
#### Example: Verify admin reset OTP
Request:
```json
{
  "email": "admin@shelfspot.com",
  "otp": "209567"
}
```
Response:
```json
{
  "success": true,
  "message": "OTP verified successfully.",
  "data": {
    "token": "eyJhbGciOi...adminReset",
    "token_type": "Bearer"
  }
}
```

### Notes
The OTP must be submitted as a string to preserve leading zeroes.

## Endpoint: Reset Admin Password
- **Method:** POST
- **URL:** /api/v1/auth/reset-password
- **Auth:** Bearer
- **Purpose:** Set a new admin password after reset OTP verification.

### Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{reset_password_token}}
```

### Request Body
```json
{
  "password": "string (required, min:8, mixed case, must be confirmed)",
  "password_confirmation": "string (required, must match password)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Password reset successfully."
}
```

### Failures
- **401 — invalid token**
```json
{ "success": false, "message": "Unauthenticated." }
```

- **403 — wrong token ability**
```json
{ "success": false, "message": "Forbidden." }
```

- **422 — validation error**
```json
{
  "message": "The password confirmation does not match.",
  "errors": {
    "password": ["The password confirmation does not match."]
  }
}
```

### Examples
#### Example: Reset admin password
Request:
```json
{
  "password": "73eQf4b1",
  "password_confirmation": "73eQf4b1"
}
```
Response:
```json
{
  "success": true,
  "message": "Password reset successfully."
}
```

### Notes
This endpoint is shared across portals. The reset-password Bearer token determines which user password is changed.

## Branch: Admin reset token obtained
**Condition:** Admin reset OTP verification succeeds and returns a reset-password token.

### Case: Submit new password
**When:** Admin chooses a new password that passes validation.
**Explanation:** Use the reset-password Bearer token to call `Reset Admin Password`, then return the admin to the login screen.

#### Endpoint: Reset Admin Password
- **Method:** POST
- **URL:** /api/v1/auth/reset-password
