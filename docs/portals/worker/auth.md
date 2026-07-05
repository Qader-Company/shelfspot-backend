# Flow: Worker Registration

## Description
Worker Registration documents how a worker creates a new account, optionally submits an initial location, verifies the email OTP, and requests a replacement OTP when needed.

## Business Goal
Allow workers to join the platform, verify email ownership, and become eligible to authenticate into the worker portal and receive nearby tasks.

## Module Overview
This flow belongs to the public Authentication module for the worker portal. Endpoints use the `/api/v1/auth` prefix with route type `worker`, require the platform API key header where configured, and use a Bearer verification token for email verification.

## Prerequisites
- Client has a valid `{{api_key}}` value for `X-Authorization`.
- Client knows the API base URL.
- Worker registration data is available: name, email, phone, password, and password confirmation.
- Worker email is unique among worker users and phone is unique among workers.
- If initial location is sent, both `latitude` and `longitude` must be sent together.

## Walkthrough
1. Submit worker identity, credentials, phone, and optional location through `Register Worker`.
2. Store the returned verification Bearer token.
3. Ask the worker to read the OTP sent to the registered email address.
4. Submit the OTP with the verification Bearer token through `Verify Worker Email OTP`.
5. If the OTP expires or is lost, call `Resend Worker Email OTP` and retry verification.

## Endpoint: Register Worker
- **Method:** POST
- **URL:** /api/v1/auth/worker/register
- **Auth:** none
- **Purpose:** Create a worker account and start email verification.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
```

### Request Body
```json
{
  "name": "string (required, max:255)",
  "email": "string (required, valid email, max:255, unique for worker users)",
  "phone": "string (required, max:255, unique in workers)",
  "password": "string (required, min:8, mixed case, must be confirmed)",
  "password_confirmation": "string (required, must match password)",
  "latitude": "number (optional, between:-90,90, required with longitude)",
  "longitude": "number (optional, between:-180,180, required with latitude)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Worker registered successfully. Please verify your email.",
  "data": {
    "token": "verification_token_value",
    "token_type": "Bearer",
    "worker": {
      "name": "Worker Doha",
      "email": "worker@example.com",
      "phone": "0109963459",
      "latitude": 25.2854,
      "longitude": 51.5310
    }
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
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field must be at least 8 characters."],
    "longitude": ["The longitude field is required when latitude is present."]
  }
}
```

### Examples
#### Example: Register worker with initial location
Request:
```json
{
  "name": "Worker Doha",
  "email": "worker@example.com",
  "phone": "0109963459",
  "password": "73eQf4b1",
  "password_confirmation": "73eQf4b1",
  "latitude": 25.2854,
  "longitude": 51.5310
}
```
Response:
```json
{
  "success": true,
  "message": "Worker registered successfully. Please verify your email.",
  "data": {
    "token": "eyJhbGciOi...verify",
    "token_type": "Bearer"
  }
}
```

### Notes
The optional location becomes useful for nearby task discovery after the worker starts using the worker portal.

## Endpoint: Verify Worker Email OTP
- **Method:** PATCH
- **URL:** /api/v1/auth/worker/email-verification
- **Auth:** Bearer
- **Purpose:** Verify worker email address using the 6-digit OTP sent to email.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
```

### Request Body
```json
{
  "otp": "string (required, exactly 6 digits)"
}
```

### Success (200)
```json
{
  "success": true,
  "message": "Email verified successfully.",
  "data": {
    "verified": true
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

- **422 — invalid otp**
```json
{
  "message": "The otp field must be 6 digits.",
  "errors": {
    "otp": ["The otp field must be 6 digits."]
  }
}
```

### Examples
#### Example: Verify worker email
Request:
```json
{
  "otp": "659438"
}
```
Response:
```json
{
  "success": true,
  "message": "Email verified successfully.",
  "data": { "verified": true }
}
```

### Notes
The OTP should be submitted as a string so leading zeroes are preserved.

## Endpoint: Resend Worker Email OTP
- **Method:** POST
- **URL:** /api/v1/auth/worker/email-verification/send-otp
- **Auth:** none
- **Purpose:** Send a new email-verification OTP to the worker email address.

### Headers
```
Accept: application/json
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
    "email": "worker@example.com"
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
#### Example: Resend worker verification OTP
Request:
```json
{
  "email": "worker@example.com"
}
```
Response:
```json
{
  "success": true,
  "message": "OTP sent successfully.",
  "data": { "email": "worker@example.com" }
}
```

### Notes
Use this endpoint only when the worker has not completed email verification or needs a replacement OTP.

## Branch: OTP retry
**Condition:** The worker did not receive the OTP, the OTP expired, or the submitted OTP failed validation.

### Case: Resend and verify again
**When:** The current OTP cannot be used.
**Explanation:** Request a new OTP for the same email, then submit the new 6-digit code to the verification endpoint.

#### Endpoint: Resend Worker Email OTP
- **Method:** POST
- **URL:** /api/v1/auth/worker/email-verification/send-otp

---
# Flow: Worker Authentication

## Description
Worker Authentication documents login, refresh token, and logout for worker users after registration and email verification.

## Business Goal
Allow workers to establish an authenticated worker portal session, renew access using a refresh token, and revoke the active session when logging out.

## Module Overview
This flow belongs to the public Authentication module and issues Bearer tokens for worker portal APIs. Login is public with API-key protection. Refresh requires a Bearer refresh token. Logout requires the active authenticated Bearer token.

## Prerequisites
- Client has a valid `{{api_key}}` for endpoints requiring `X-Authorization`.
- Worker account exists and can authenticate.
- For refresh, client already has a valid refresh Bearer token.
- For logout, client has the active worker Bearer token.

## Walkthrough
1. Submit worker email and password through `Login Worker`.
2. Store the returned worker access token for protected worker portal APIs.
3. Store the returned refresh token separately.
4. Call `Refresh Worker Token` when the access token expires.
5. Call `Logout Worker` when the worker signs out.

## Endpoint: Login Worker
- **Method:** POST
- **URL:** /api/v1/auth/worker/login
- **Auth:** none
- **Purpose:** Authenticate worker credentials and issue worker access and refresh tokens.

### Headers
```
Accept: application/json
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
    "access_token": "worker_access_token_value",
    "refresh_token": "worker_refresh_token_value",
    "token_type": "Bearer"
  }
}
```

### Failures
- **401 — invalid credentials**
```json
{ "success": false, "message": "Invalid credentials." }
```

- **422 — validation error**
```json
{
  "message": "The password field must be at least 6 characters.",
  "errors": {
    "password": ["The password field must be at least 6 characters."]
  }
}
```

### Examples
#### Example: Login worker
Request:
```json
{
  "email": "worker@example.com",
  "password": "73eQf4b1"
}
```
Response:
```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "access_token": "eyJhbGciOi...workerAccess",
    "refresh_token": "eyJhbGciOi...workerRefresh",
    "token_type": "Bearer"
  }
}
```

### Notes
Use the worker access token with routes under `/api/v1/worker/*`.

## Endpoint: Refresh Worker Token
- **Method:** POST
- **URL:** /api/v1/auth/worker/refresh
- **Auth:** Bearer
- **Purpose:** Exchange a valid worker refresh token for a new token pair.

### Headers
```
Accept: application/json
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
    "access_token": "new_worker_access_token_value",
    "refresh_token": "new_worker_refresh_token_value",
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

### Examples
#### Example: Refresh worker token
Request:
```json
{
  "email": "worker@example.com",
  "password": "73eQf4b1"
}
```
Response:
```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "access_token": "eyJhbGciOi...newWorkerAccess",
    "refresh_token": "eyJhbGciOi...newWorkerRefresh",
    "token_type": "Bearer"
  }
}
```

### Notes
This endpoint requires a refresh-token Bearer token, not a normal access token.

## Endpoint: Logout Worker
- **Method:** DELETE
- **URL:** /api/v1/auth/logout
- **Auth:** Bearer
- **Purpose:** Revoke the active worker token/session.

### Headers
```
Accept: application/json
Authorization: Bearer {{token}}
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

### Examples
#### Example: Logout worker session
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

## Branch: Worker session renewal
**Condition:** Worker access token expired but refresh token is still valid.

### Case: Refresh and continue session
**When:** Worker APIs return authentication failure due to access token expiry.
**Explanation:** Call `Refresh Worker Token`, store the returned access token, and retry the protected worker API call.

#### Endpoint: Refresh Worker Token
- **Method:** POST
- **URL:** /api/v1/auth/worker/refresh

---
# Flow: Worker Reset Password

## Description
Worker Reset Password documents how a worker requests a reset OTP, verifies the OTP, receives a reset-password token, and sets a new password.

## Business Goal
Allow workers who forgot their password to regain access by proving ownership of their email address.

## Module Overview
This flow belongs to the public Authentication module. OTP request and OTP verification are public API-key-protected steps. The final reset call requires a Bearer reset-password token.

## Prerequisites
- Client knows the worker registered email.
- Worker can access the email inbox to read the OTP.
- Client has a valid `{{api_key}}` where required.
- New password must be at least 8 characters, mixed case, and confirmed.

## Walkthrough
1. Submit worker email through `Ask for Worker Reset OTP`.
2. Ask the worker to read the OTP sent by email.
3. Submit email and OTP through `Verify Worker Reset OTP`.
4. Store the returned reset-password Bearer token.
5. Submit new password and confirmation through `Reset Worker Password`.

## Endpoint: Ask for Worker Reset OTP
- **Method:** POST
- **URL:** /api/v1/auth/worker/password-reset/send-otp
- **Auth:** none
- **Purpose:** Send a password-reset OTP to the worker email.

### Headers
```
Accept: application/json
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
  "data": { "email": "worker@example.com" }
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
#### Example: Request worker reset OTP
Request:
```json
{
  "email": "worker@example.com"
}
```
Response:
```json
{
  "success": true,
  "message": "OTP sent successfully.",
  "data": { "email": "worker@example.com" }
}
```

### Notes
The OTP is used only to verify reset-password permission.

## Endpoint: Verify Worker Reset OTP
- **Method:** POST
- **URL:** /api/v1/auth/worker/reset-password-verification
- **Auth:** none
- **Purpose:** Verify reset-password OTP and issue a reset-password token.

### Headers
```
Accept: application/json
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
#### Example: Verify worker reset OTP
Request:
```json
{
  "email": "worker@example.com",
  "otp": "209567"
}
```
Response:
```json
{
  "success": true,
  "message": "OTP verified successfully.",
  "data": {
    "token": "eyJhbGciOi...workerReset",
    "token_type": "Bearer"
  }
}
```

### Notes
Use the returned token in the final reset password call.

## Endpoint: Reset Worker Password
- **Method:** POST
- **URL:** /api/v1/auth/reset-password
- **Auth:** Bearer
- **Purpose:** Set a new worker password after reset OTP verification.

### Headers
```
Accept: application/json
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
#### Example: Reset worker password
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
This endpoint is shared across portals; token ability determines that the caller is allowed to reset the password.

## Branch: Reset token obtained
**Condition:** Worker reset-password OTP verification succeeded and returned a reset-password token.

### Case: Submit new password
**When:** Worker chooses a valid new password.
**Explanation:** Use the reset-password Bearer token to submit `password` and `password_confirmation` to the shared reset endpoint.

#### Endpoint: Reset Worker Password
- **Method:** POST
- **URL:** /api/v1/auth/reset-password
