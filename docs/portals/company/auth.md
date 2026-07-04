# Flow: Company Registration

## Description
Company Registration documents how a company creates a new account, receives a verification token, verifies the email OTP, and can request another email-verification OTP when needed.

## Business Goal
Allow a company owner to create a company account, prove ownership of the email address, and become eligible to use authenticated company portal APIs.

## Module Overview
This flow belongs to the public Authentication module for the company portal. The API uses the public auth prefix `/api/v1/auth`, the company route type `company`, the API key header `X-Authorization`, and a short-lived Bearer verification token for email verification.

## Prerequisites
- Client has a valid `{{api_key}}` value for the `X-Authorization` header where required.
- Client knows the API base URL represented by `{{local}}`.
- Company registration data is available: name, email, phone, password, password confirmation, CR number, and industry.
- The email and phone are not already registered for another company.
- The selected industry is one of the backend-supported company industry values.

## Walkthrough
1. Submit company profile and owner credentials through `Register`.
2. Store the verification Bearer token returned by registration as `{{token}}`.
3. Ask the company owner to read the OTP sent to the registered email address.
4. Submit the OTP with the verification Bearer token through `Verify OTP`.
5. If the OTP expires or is lost, call `Resend OTP` with the company email, then retry verification.

## Endpoint: Register
- **Method:** POST
- **URL:** /api/v1/auth/company/register
- **Auth:** none
- **Purpose:** Create a new company account and start email verification.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
```

### Request Body
```json
{
  "name": "string (required, max:255)",
  "email": "string (required, valid email, max:255, unique for companies and company users)",
  "phone": "string (required, max:255, unique for companies)",
  "password": "string (required, min:8, mixed case, must be confirmed)",
  "password_confirmation": "string (required, must match password)",
  "cr_number": "string (required, max:255)",
  "industry": "string (required, must be a supported company industry value)"
}
```

### Success (200)
```json
{
  "message": "Company registered successfully. Please verify your email.",
  "data": {
    "token": "verification_token_value",
    "token_type": "Bearer",
    "company": {
      "name": "company doha",
      "email": "doha@gmail.com",
      "phone": "0109963459",
      "cr_number": "9912987823",
      "industry": "industry_one"
    }
  }
}
```

### Failures
- **401 — invalid api key**
```json
{ "error": "Invalid API key." }
```

- **422 — validation error**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

### Examples
#### Example: Register company owner
Request:
```json
{
  "name": "company doha",
  "email": "doha@gmail.com",
  "phone": "0109963459",
  "password": "73eQf4b1",
  "password_confirmation": "73eQf4b1",
  "cr_number": "9912987823",
  "industry": "industry_one"
}
```
Response:
```json
{
  "message": "Company registered successfully. Please verify your email.",
  "data": {
    "token": "eyJhbGciOi...verify",
    "token_type": "Bearer"
  }
}
```

### Notes
The registration endpoint is public, but it still requires the platform API key in `X-Authorization`. The returned token should be treated as an email-verification token and used only for the email verification step.

## Endpoint: Verify OTP
- **Method:** PATCH
- **URL:** /api/v1/auth/company/email-verification
- **Auth:** Bearer
- **Purpose:** Verify the company email address using the OTP sent to the registered email.

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
  "message": "Email verified successfully.",
  "data": {
    "verified": true
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "error": "Unauthenticated." }
```

- **403 — wrong token ability**
```json
{ "error": "Token does not have the required ability." }
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
#### Example: Verify registration OTP
Request:
```json
{
  "otp": "659438"
}
```
Response:
```json
{
  "message": "Email verified successfully.",
  "data": {
    "verified": true
  }
}
```

### Notes
This endpoint requires a Bearer token with the email verification ability. The OTP must be submitted as a string to preserve leading zeroes.

## Endpoint: Resend OTP
- **Method:** POST
- **URL:** /api/v1/auth/company/email-verification/send-otp
- **Auth:** none
- **Purpose:** Send a new email-verification OTP to a company email address.

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
  "message": "OTP sent successfully.",
  "data": {
    "email": "test2@gmail.com"
  }
}
```

### Failures
- **401 — invalid api key**
```json
{ "error": "Invalid API key." }
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

- **429 — too many otp requests**
```json
{ "message": "Too many attempts. Please try again later." }
```

### Examples
#### Example: Resend company email verification OTP
Request:
```json
{
  "email": "test2@gmail.com"
}
```
Response:
```json
{
  "message": "OTP sent successfully.",
  "data": {
    "email": "test2@gmail.com"
  }
}
```

### Notes
Use this endpoint only when the company has not completed email verification or needs a replacement OTP.

## Branch: OTP retry
**Condition:** The company owner did not receive the OTP, the OTP expired, or the submitted OTP failed validation.

### Case: Resend and verify again
**When:** The current OTP cannot be used.
**Explanation:** Request a new OTP for the same email, then submit the new 6-digit code to the verification endpoint.

#### Endpoint: Resend OTP
- **Method:** POST
- **URL:** /api/v1/auth/company/email-verification/send-otp

---
# Flow: Company Authentication

## Description
Company Authentication documents login, refresh token, and logout for company users after the company account can authenticate.

## Business Goal
Allow company users to establish an authenticated session, renew access using a refresh token, and revoke the current session when they log out.

## Module Overview
This flow belongs to the public Authentication module and issues Bearer tokens used by protected company portal APIs. Login is public with API-key protection. Refresh requires a Bearer refresh token. Logout requires an authenticated Bearer token.

## Prerequisites
- Client has a valid `{{api_key}}` value for endpoints that include `X-Authorization`.
- The company user account exists.
- The company user knows the registered email and password.
- For refresh, the client already has a valid refresh Bearer token in `{{token}}`.
- For logout, the client already has a valid authenticated Bearer token in `{{admin}}` or the active session token variable used by the client.

## Walkthrough
1. Submit company email and password through `Login`.
2. Store the returned access token for company portal API calls.
3. Store the returned refresh token separately for token renewal.
4. When the access token expires, call `Refresh Token` with the refresh Bearer token.
5. When the user signs out, call `Logout` with the active Bearer token.

## Endpoint: Login
- **Method:** POST
- **URL:** /api/v1/auth/company/login
- **Auth:** none
- **Purpose:** Authenticate a company user and issue access and refresh tokens.

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
  "message": "Logged in successfully.",
  "data": {
    "access_token": "company_access_token_value",
    "refresh_token": "company_refresh_token_value",
    "token_type": "Bearer"
  }
}
```

### Failures
- **401 — invalid credentials**
```json
{ "error": "Invalid credentials." }
```

- **401 — invalid api key**
```json
{ "error": "Invalid API key." }
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
#### Example: Login company user
Request:
```json
{
  "email": "test21@gmail.com",
  "password": "73eQf4b1"
}
```
Response:
```json
{
  "message": "Logged in successfully.",
  "data": {
    "access_token": "eyJhbGciOi...access",
    "refresh_token": "eyJhbGciOi...refresh",
    "token_type": "Bearer"
  }
}
```

### Notes
The access token is intended for protected company portal APIs. The refresh token should be stored securely and used only with the refresh endpoint.

## Endpoint: Refresh Token
- **Method:** POST
- **URL:** /api/v1/auth/company/refresh
- **Auth:** Bearer
- **Purpose:** Exchange a valid company refresh token for a new token pair.

### Headers
```
Accept: application/json
X-Authorization: {{api_key}}
Authorization: Bearer {{token}}
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
  "message": "Token refreshed successfully.",
  "data": {
    "access_token": "new_company_access_token_value",
    "refresh_token": "new_company_refresh_token_value",
    "token_type": "Bearer"
  }
}
```

### Failures
- **401 — invalid token**
```json
{ "error": "Unauthenticated." }
```

- **403 — wrong token ability**
```json
{ "error": "Token does not have the required ability." }
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
#### Example: Refresh company token
Request:
```json
{
  "email": "test2@gmail.com",
  "password": "12345678"
}
```
Response:
```json
{
  "message": "Token refreshed successfully.",
  "data": {
    "access_token": "eyJhbGciOi...newAccess",
    "refresh_token": "eyJhbGciOi...newRefresh",
    "token_type": "Bearer"
  }
}
```

### Notes
This endpoint requires a Bearer token with the refresh-token ability. Do not call it with the normal access token.

## Endpoint: Logout
- **Method:** DELETE
- **URL:** /api/v1/auth/logout
- **Auth:** Bearer
- **Purpose:** Revoke the current authenticated token/session.

### Headers
```
Accept: application/json
Authorization: Bearer {{admin}}
```

### Request Body
```json
{}
```

### Success (200)
```json
{
  "message": "Logged out successfully."
}
```

### Failures
- **401 — invalid token**
```json
{ "error": "Unauthenticated." }
```

- **429 — too many logout requests**
```json
{ "message": "Too many attempts. Please try again later." }
```

### Examples
#### Example: Logout active company session
Request:
```json
{}
```
Response:
```json
{
  "message": "Logged out successfully."
}
```

### Notes
The input collection uses `{{admin}}` as the token variable for logout. For company clients, pass the active company Bearer token for the session that should be revoked.

## Branch: Token renewal
**Condition:** The company access token expired but the refresh token is still valid.

### Case: Refresh access
**When:** Protected company portal APIs return an authentication error because the access token expired.
**Explanation:** Call the refresh endpoint with the refresh Bearer token, replace the stored access token, and continue the user session.

#### Endpoint: Refresh Token
- **Method:** POST
- **URL:** /api/v1/auth/company/refresh

---
# Flow: Company Reset Password

## Description
Company Reset Password documents how a company user requests a reset OTP, verifies the OTP, receives a reset-password token, and submits a new password.

## Business Goal
Allow company users who forgot their password to securely prove email ownership and set a new password without using the old password.

## Module Overview
This flow belongs to the public Authentication module. OTP request and OTP verification are public API-key-protected steps. The final reset password call requires a Bearer reset-password token returned after successful OTP verification.

## Prerequisites
- Client knows the company user's registered email address.
- Client has access to the email inbox to read the OTP.
- Client has a valid `{{api_key}}` where API-key middleware is required.
- The new password is at least 8 characters, mixed case, and matches `password_confirmation`.

## Walkthrough
1. Submit the company email through `Ask for OTP`.
2. Ask the company user to read the OTP sent to the email address.
3. Submit the email and OTP through `Verify OTP`.
4. Store the reset-password Bearer token returned by OTP verification.
5. Submit the new password and confirmation through `Reset password` using the reset-password Bearer token.

## Endpoint: Ask for OTP
- **Method:** POST
- **URL:** /api/v1/auth/company/password-reset/send-otp
- **Auth:** none
- **Purpose:** Send a password-reset OTP to the company user's email address.

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
  "message": "OTP sent successfully.",
  "data": {
    "email": "test3@gmail.com"
  }
}
```

### Failures
- **401 — invalid api key**
```json
{ "error": "Invalid API key." }
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

- **429 — too many otp requests**
```json
{ "message": "Too many attempts. Please try again later." }
```

### Examples
#### Example: Request reset password OTP
Request:
```json
{
  "email": "test3@gmail.com"
}
```
Response:
```json
{
  "message": "OTP sent successfully.",
  "data": {
    "email": "test3@gmail.com"
  }
}
```

### Notes
The OTP is used only to verify permission to reset the password. It is not the final reset credential.

## Endpoint: Verify OTP
- **Method:** POST
- **URL:** /api/v1/auth/company/reset-password-verification
- **Auth:** none
- **Purpose:** Verify the password-reset OTP and issue a reset-password Bearer token.

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
  "message": "OTP verified successfully.",
  "data": {
    "token": "reset_password_token_value",
    "token_type": "Bearer"
  }
}
```

### Failures
- **401 — invalid api key**
```json
{ "error": "Invalid API key." }
```

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
#### Example: Verify reset password OTP
Request:
```json
{
  "email": "test3@gmail.com",
  "otp": "209567"
}
```
Response:
```json
{
  "message": "OTP verified successfully.",
  "data": {
    "token": "eyJhbGciOi...resetPassword",
    "token_type": "Bearer"
  }
}
```

### Notes
The OTP must be sent as a 6-digit string. Use the returned token in the final reset password call.

## Endpoint: Reset password
- **Method:** POST
- **URL:** /api/v1/auth/reset-password
- **Auth:** Bearer
- **Purpose:** Set a new password after reset OTP verification.

### Headers
```
Accept: application/json
Authorization: Bearer {{admin}}
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
  "message": "Password reset successfully."
}
```

### Failures
- **401 — invalid token**
```json
{ "error": "Unauthenticated." }
```

- **403 — wrong token ability**
```json
{ "error": "Token does not have the required ability." }
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
#### Example: Reset company password
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
  "message": "Password reset successfully."
}
```

### Notes
The input collection uses `{{admin}}` as the token variable for the final reset step. For company password reset, pass the reset-password Bearer token returned by the reset OTP verification endpoint.

## Branch: Reset token obtained
**Condition:** The reset-password OTP verification succeeded and returned a reset-password Bearer token.

### Case: Submit new password
**When:** The company user has chosen a new password that satisfies password rules.
**Explanation:** Use the reset-password Bearer token once to submit the new password and password confirmation.

#### Endpoint: Reset password
- **Method:** POST
- **URL:** /api/v1/auth/reset-password
