# Flow Review: Authentication / Registration / Tokens

## Scope

### Portals

- Public portal for registration, login, OTP, email verification and password reset.
- Authenticated portal-specific follow-up calls that use Sanctum token abilities.

### Routes

- `POST /api/v1/auth/{type}/register` for `company|worker`.
- `POST /api/v1/auth/{type}/login` for `admin|company|worker`.
- `DELETE /api/v1/auth/logout`.
- `POST /api/v1/auth/{type}/refresh`.
- `POST /api/v1/auth/{purpose}/send-otp`.
- `PATCH /api/v1/auth/{type}/email-verification` for `company|worker`.
- `POST /api/v1/auth/{type}/reset-password-verification`.
- `POST /api/v1/auth/reset-password`.

### Main files

- `routes/V1/public/auth.php`
- `app/Modules/V1/Authentication/Presentation/Http/Controller/AuthController.php`
- `app/Modules/V1/Authentication/Presentation/Http/Controller/EmailVerificationController.php`
- `app/Modules/V1/Authentication/Presentation/Http/Controller/ResetPasswordController.php`
- `app/Modules/V1/Authentication/Application/UseCases/RegisterUseCase.php`
- `app/Modules/V1/Authentication/Application/UseCases/LogInUseCase.php`
- `app/Modules/V1/Authentication/Application/UseCases/SendOtpUseCase.php`
- `app/Modules/V1/Authentication/Application/UseCases/VerifyEmailUseCase.php`
- `app/Modules/V1/Authentication/Application/UseCases/VerifyResetPasswordOTPUseCase.php`
- `app/Modules/V1/Authentication/Domain/Services/TokenIssuer.php`
- `app/Modules/V1/Authentication/Domain/Services/OtpService.php`
- `app/Modules/V1/Authentication/Presentation/Http/Requests/*`
- `app/Modules/V1/Companies/Presentation/Http/Requests/RegisterCompanyRequest.php`
- `app/Modules/V1/Workers/Presentation/Http/Requests/RegisterWorkerRequest.php`

## Current implementation summary

### 1. Register

- Route allows only `company` and `worker` registration.
- `RegisterRequest` switches validation rules by route `type`.
- `RegisterUseCase` wraps the flow in a database transaction.
- Company registration delegates to `CreateCompanyWithOwnerUseCase`.
- Worker registration delegates to `CreateWorkerUseCase`.
- After creating the user, the system sends an email verification OTP and creates a Sanctum verification token.

### 2. Login

- Route accepts all portal types: `admin`, `company`, and `worker`.
- `LogInUseCase` looks up user by `email` and `type`.
- It checks portal-specific activation using `UserActivationChecker`.
- If the email is not verified, the response returns a verification token and sends another email verification OTP.
- If verified, `TokenIssuer::refreshToken` issues a new access token and refresh token.

### 3. Token issuing / refresh

- `TokenIssuer::create` creates Sanctum tokens with two abilities: token type and portal.
- `TokenIssuer::refreshToken` deletes the current token then creates an access token and refresh token.
- Refresh route requires auth with `refresh` ability.

### 4. Email verification

- Email verification route requires `auth:sanctum` and the verification-token ability.
- `VerifyEmailUseCase` validates the OTP, marks `email_verified_at`, deletes current token through `refreshToken`, then returns access/refresh tokens.

### 5. Password reset

- User requests OTP through the generic send-OTP endpoint.
- User verifies reset OTP via `reset-password-verification` and receives a reset-password token.
- User calls reset-password with a reset-password token.
- Controller updates the password and then deletes all tokens.

## Issues / gaps found

### P0 - Reset-password OTP is not purpose-isolated

`OtpService` generates and validates OTPs by email identifier only. The `purpose` is used only in the email template, not in the OTP storage key. This means an OTP generated for email verification can be used for reset-password verification, and vice versa, as long as it is valid for the same email.

**Why it matters:** reset password is a sensitive flow. OTPs must be scoped by purpose to prevent cross-purpose token reuse.

**Suggested fix:** use a purpose-aware identifier such as `email|purpose`, or store purpose explicitly and validate `(email, purpose, token)`.

### P0 - `send-otp` can generate reset-password OTP for any existing user without portal/type validation

`SendOtpUseCase` finds the user by email only and does not check the requested portal or whether the user belongs to that portal. The route is `/{purpose}/send-otp`, so the caller does not provide `type` at all.

**Why it matters:** if the same email can exist across different portals, the endpoint may send an OTP for the wrong account context. It also makes reset-password verification less deterministic because the next endpoint does include `{type}`.

**Suggested fix:** change the API contract to include portal type for OTP requests, or require the reset-password verification use case to lookup by both `email` and `type`, and make OTP identifier include portal + purpose.

### P0 - Password reset verification does not lookup user by portal type

`VerifyResetPasswordOTPUseCase` receives a `PortalTypeEnum $type`, but the lookup is only `email`. The created reset token uses the requested `$type` ability, even if the found user belongs to another portal type.

**Why it matters:** this can issue a reset token with mismatched portal abilities for the wrong user when emails overlap between portals or validation rules allow it.

**Suggested fix:** lookup by `email` + `type`, and fail with a generic invalid response if no matching user exists.

### P0 - Company registration validates email uniqueness against `companies`, not `users`

`RegisterCompanyRequest` checks `unique:companies,email`, but `CreateCompanyUserUseCase` creates a row in `users` with the same email and `type=company`. If the `users` table is the login source, registration should protect the user identity constraints too.

**Why it matters:** the login flow uses `users.email + users.type`. A duplicate company user email can break account creation with database errors or ambiguous auth behavior depending on DB constraints.

**Suggested fix:** validate uniqueness against `users` scoped to `type=company`, and keep company email uniqueness if company profile email must also be unique.

### P1 - Verification token is returned under inconsistent response keys

Register returns `verification_token`, while login for an unverified account returns `verify_token`.

**Why it matters:** frontend integration becomes brittle and must special-case equivalent auth states.

**Suggested fix:** standardize on one key, preferably `verification_token`, across register and unverified-login.

### P1 - Register transaction includes sending email

`RegisterUseCase` sends OTP email inside the database transaction. If mail sending fails, the database transaction rolls back. If the transaction rolls back after the mail is sent, the user may receive an OTP for an account that does not exist.

**Why it matters:** external side effects inside DB transactions can create inconsistent user experience and make failures hard to retry.

**Suggested fix:** create user inside the transaction, then send OTP after commit using `DB::afterCommit()` or a queued mail job.

### P1 - Login for unverified users sends an OTP on every login attempt

When credentials are valid but email is unverified, `LogInUseCase` always generates and sends a new OTP.

**Why it matters:** even with route throttling, repeated login attempts can spam email and invalidate previously sent OTP codes unexpectedly.

**Suggested fix:** implement OTP resend cooldown/idempotency, or only send a new code from the explicit `send-otp` endpoint.

### P1 - Refresh-token rotation deletes only the current refresh token

`TokenIssuer::refreshToken` deletes the current token and creates new access/refresh tokens. Other existing refresh tokens remain valid.

**Why it matters:** token reuse detection and session management are weak. If a refresh token is leaked, rotating one token does not revoke other refresh tokens for the same user/session.

**Suggested fix:** decide product policy: either allow multi-session explicitly and track device/session IDs, or revoke/rotate refresh tokens per session and detect reused revoked refresh tokens.

### P1 - Logout deletes only the current token

Logout deletes only `currentAccessToken`. If called with an access token, the associated refresh token remains valid unless the client discards it.

**Why it matters:** a user can appear logged out while a refresh token still allows generating a new access token.

**Suggested fix:** tie access and refresh tokens to a session identifier and revoke the whole session on logout, or expose `logout-all` separately.

### P1 - Reset-password updates password before deleting tokens

`ResetPasswordController` updates password and then deletes tokens. If token deletion fails, the password is changed but existing tokens may remain valid.

**Why it matters:** after password reset, old sessions should be invalidated reliably.

**Suggested fix:** wrap password update + token deletion in a DB transaction, or delete tokens first then update password depending on desired failure behavior.

### P2 - Request composition creates new FormRequest instances manually

`LoginRequest`, `RegisterRequest`, and `VerifyResetPasswordOTPRequest` instantiate other `FormRequest` classes directly to reuse rules.

**Why it matters:** manual FormRequest construction skips request lifecycle/context and can become fragile if reused requests later depend on route/user/request data.

**Suggested fix:** extract shared rules into small rule-provider classes or static methods.

### P2 - `PortalTypeEnum::tryFrom` results are not explicitly guarded in controllers

Routes constrain most values, but controllers pass `tryFrom` results directly to use cases typed as `PortalTypeEnum`.

**Why it matters:** route constraints protect current routes, but direct/controller tests or future route changes can produce a `TypeError` instead of a clean API error.

**Suggested fix:** use route model binding-style enum constraints where possible, or explicitly handle `null` and return validation error.

## Performance notes

- Login does one user lookup, then loads related portal model only inside `UserActivationChecker`. This is acceptable for one request, but could be optimized with eager loading based on portal type.
- Token creation writes two `personal_access_tokens` rows per successful login/refresh. This is expected, but stale tokens need scheduled pruning based on `expires_at`.
- OTP sends are synchronous mail sends. Queuing mail would improve response latency and reduce request timeout risk.

## Proposed implementation plan

### Step 1 - Secure OTP scoping

1. Make OTP identifier include `portal + purpose + email`.
2. Update generate/validate methods to require purpose and optionally portal.
3. Update email verification, reset-password verification, register, login unverified, and send-OTP flows.
4. Add tests proving an email-verification OTP cannot reset password.

### Step 2 - Fix user lookup and uniqueness consistency

1. Update reset-password verification lookup to `email + type`.
2. Update company registration email rules to validate against `users` scoped to company type.
3. Recheck migrations/indexes for expected unique constraints.

### Step 3 - Normalize API responses

1. Standardize token response keys.
2. Document response contract for register/login/verify/reset.
3. Add feature tests for response shapes.

### Step 4 - Improve token/session lifecycle

1. Decide multi-session policy.
2. Add session/device identifier if refresh/access token pairing is required.
3. Revoke refresh token on logout for the same session.
4. Add token pruning command/schedule if missing.

**Product decision:** multi-session is allowed. Logout should revoke only the current token/session, not all user sessions. Access/refresh pairing can be added later with a `session_id`/device identifier if product needs per-device session management, suspicious refresh-token reuse detection, or “logout this device” semantics that revoke both the current access token and its paired refresh token.

### Step 5 - Move external side effects after commit

1. Send OTP mails after DB commit.
2. Queue OTP mail where possible.
3. Add tests/fakes around mail sending.

**Implementation note:** OTP mails are queued after commit so the request does not wait for SMTP delivery and no OTP email is dispatched before the surrounding database transaction is committed.

## Tests needed

### Feature tests

- Company register returns user + verification token and creates company owner.
- Worker register returns user + verification token and creates worker profile.
- Login verified company/admin/worker returns access and refresh tokens with correct abilities.
- Login unverified user returns verification token with consistent key.
- Email verification accepts only email-verification OTPs.
- Reset-password verification accepts only reset-password OTPs for the same portal.
- Reset password invalidates previous tokens.
- Logout invalidates refresh capability for the same session if session pairing is implemented.

### Unit tests

- `TokenIssuer` creates expected abilities and TTLs.
- `OtpService` scopes identifiers by purpose and portal.
- `UserActivationChecker` rejects inactive company/admin/worker contexts.

## Suggested next flow

After fixing or accepting the auth findings, the next best flow is **Tenant + Access Control**, because all company/admin catalog and task routes depend on it for security boundaries.
