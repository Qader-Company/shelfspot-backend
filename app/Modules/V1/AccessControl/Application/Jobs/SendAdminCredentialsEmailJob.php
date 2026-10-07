<?php

namespace App\Modules\V1\AccessControl\Application\Jobs;

use App\Modules\V1\AccessControl\Application\Mail\AdminCredentialsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdminCredentialsEmailJob implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $name,
        private readonly string $email,
        private readonly string $password,
        private readonly string $portal,
        private readonly bool $passwordUpdated = false,
    ) {
        $this->onQueue(config('notifications.queues.normal'));
        $this->afterCommit();
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new AdminCredentialsMail(
            name: $this->name,
            email: $this->email,
            password: $this->password,
            portal: $this->portal,
            passwordUpdated: $this->passwordUpdated,
        ));
    }
}
