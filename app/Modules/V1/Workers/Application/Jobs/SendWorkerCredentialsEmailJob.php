<?php

namespace App\Modules\V1\Workers\Application\Jobs;

use App\Modules\V1\Workers\Application\Mail\WorkerCredentialsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWorkerCredentialsEmailJob implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $name,
        private readonly string $email,
        private readonly string $password,
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new WorkerCredentialsMail(
            name: $this->name,
            email: $this->email,
            password: $this->password,
            applicationUrl: config('app.worker_app_url'),
        ));
    }
}
