<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $config = config('rate-limiting.login');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by($this->loginKey($request));
        });

        RateLimiter::for('auth-register', function (Request $request) {
            $config = config('rate-limiting.register');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by($request->ip());
        });

        RateLimiter::for('auth-otp-send', function (Request $request) {
            $config = config('rate-limiting.otp_send');
            $email = (string) $request->input('email', '');

            return [
                Limit::perMinutes(
                    $config['decay_minutes'],
                    $config['per_email']
                )->by('email:'.$email),
                Limit::perMinutes(
                    $config['decay_minutes'],
                    $config['per_ip']
                )->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('auth-otp-verify', function (Request $request) {
            $config = config('rate-limiting.otp_verify');
            $email = (string) ($request->input('email') ?? $request->user()?->email ?? '');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            $config = config('rate-limiting.reset_password');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('auth-refresh', function (Request $request) {
            $config = config('rate-limiting.refresh');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('auth-logout', function (Request $request) {
            $config = config('rate-limiting.logout');

            return Limit::perMinutes(
                $config['decay_minutes'],
                $config['max_attempts']
            )->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }

    private function loginKey(Request $request): string
    {
        $email = (string) $request->input('email', '');

        return $email.'|'.$request->ip();
    }
}
