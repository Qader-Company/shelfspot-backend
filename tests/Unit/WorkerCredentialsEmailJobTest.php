<?php

namespace Tests\Unit;

use App\Modules\V1\Workers\Application\Jobs\SendWorkerCredentialsEmailJob;
use App\Modules\V1\Workers\Application\Mail\WorkerCredentialsMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WorkerCredentialsEmailJobTest extends TestCase
{
    public function test_it_sends_the_new_worker_credentials_and_application_link(): void
    {
        config()->set('app.worker_app_url', 'https://workers.example.test/download');
        Mail::fake();

        (new SendWorkerCredentialsEmailJob(
            name: 'Jane Worker',
            email: 'jane@example.test',
            password: 'Secret123',
        ))->handle();

        Mail::assertSent(WorkerCredentialsMail::class, function (WorkerCredentialsMail $mail): bool {
            return $mail->hasTo('jane@example.test')
                && $mail->name === 'Jane Worker'
                && $mail->email === 'jane@example.test'
                && $mail->password === 'Secret123'
                && $mail->applicationUrl === 'https://workers.example.test/download';
        });
    }
}
