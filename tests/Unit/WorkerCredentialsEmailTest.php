<?php

namespace Tests\Unit;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\Jobs\SendWorkerCredentialsEmailJob;
use App\Modules\V1\Workers\Application\Mail\WorkerCredentialsMail;
use App\Modules\V1\Workers\Application\UseCases\CreateWorkerUseCase;
use App\Modules\V1\Workers\Application\UseCases\UpdateWorkerUseCase;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Modules\V1\Workers\Presentation\Http\Controllers\AdminWorkerController;
use App\Modules\V1\Workers\Presentation\Http\Requests\RegisterWorkerRequest;
use App\Modules\V1\Workers\Presentation\Http\Requests\UpdateWorkerRequest;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkerCredentialsEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['app.worker_app_url' => 'https://worker.example.com']);
    }

    public static function accountEvents(): array
    {
        return [
            'created' => [false],
            'password updated' => [true],
        ];
    }

    #[DataProvider('accountEvents')]
    public function test_email_includes_credentials_and_the_correct_account_event(bool $passwordUpdated): void
    {
        $job = new SendWorkerCredentialsEmailJob(
            name: 'Test Worker',
            email: 'worker@example.com',
            password: 'Pass<&123!>',
            passwordUpdated: $passwordUpdated,
        );
        $job->handle();

        Mail::assertSentCount(1);
        Mail::assertSent(WorkerCredentialsMail::class, function (WorkerCredentialsMail $mail) use ($passwordUpdated): bool {
            $html = $mail->render();
            $this->assertStringContainsString('Hello Test Worker,', $html);
            $this->assertStringContainsString('worker@example.com', $html);
            $this->assertStringContainsString('Pass&lt;&amp;123!&gt;', $html);
            $this->assertStringNotContainsString('Pass<&123!>', $html);
            $this->assertStringContainsString('https://worker.example.com', $html);
            $this->assertStringContainsString(
                $passwordUpdated ? 'password has been updated' : 'account has been created',
                $html,
            );
            $this->assertStringContainsString(
                $passwordUpdated ? 'worker password has been updated' : 'worker account',
                $mail->envelope()->subject,
            );

            return $mail->hasTo('worker@example.com')
                && $mail->password === 'Pass<&123!>'
                && $mail->passwordUpdated === $passwordUpdated;
        });
    }

    public function test_creation_controller_sends_credentials_after_creation_succeeds(): void
    {
        $attributes = ['name' => 'Test Worker', 'email' => 'worker@example.com', 'password' => 'InitialPass123!'];
        $request = Mockery::mock(RegisterWorkerRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($attributes);
        $create = Mockery::mock(CreateWorkerUseCase::class);
        $create->shouldReceive('execute')->once()->with($attributes)->andReturn($this->worker()->user);

        $response = $this->controller()->store($request, $create);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCredentialsSent('InitialPass123!', passwordUpdated: false);
    }

    public function test_update_controller_emails_the_new_password_to_the_updated_address(): void
    {
        $attributes = ['email' => 'updated@example.com', 'password' => 'NewPass123!'];
        $request = Mockery::mock(UpdateWorkerRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($attributes);
        $update = Mockery::mock(UpdateWorkerUseCase::class);
        $update->shouldReceive('execute')->once()->with(42, $attributes)->andReturn($this->worker('updated@example.com'));

        $response = $this->controller()->update($request, 42, $update);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCredentialsSent('NewPass123!', passwordUpdated: true, email: 'updated@example.com');
    }

    public function test_update_without_a_password_does_not_send_credentials(): void
    {
        $attributes = ['name' => 'Updated Worker', 'email' => 'updated@example.com', 'is_active' => false];
        $request = Mockery::mock(UpdateWorkerRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($attributes);
        $update = Mockery::mock(UpdateWorkerUseCase::class);
        $update->shouldReceive('execute')->once()->with(42, $attributes)->andReturn($this->worker('updated@example.com'));

        $response = $this->controller()->update($request, 42, $update);

        $this->assertSame(200, $response->getStatusCode());
        Mail::assertNothingSent();
    }

    public function test_failed_update_does_not_send_credentials(): void
    {
        $attributes = ['password' => 'NewPass123!'];
        $request = Mockery::mock(UpdateWorkerRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($attributes);
        $update = Mockery::mock(UpdateWorkerUseCase::class);
        $update->shouldReceive('execute')->once()->with(42, $attributes)->andThrow(new \RuntimeException('Save failed.'));

        try {
            $this->controller()->update($request, 42, $update);
            $this->fail('Expected the failed save to throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Save failed.', $exception->getMessage());
        }

        Mail::assertNothingSent();
    }

    public function test_queued_password_is_encrypted_and_can_be_delivered(): void
    {
        $payload = null;
        Queue::before(function (JobProcessing $event) use (&$payload): void {
            $payload = $event->job->getRawBody();
        });

        $this->dispatchCredentials();

        $this->assertNotNull($payload);
        $this->assertStringNotContainsString('SecretPass123!', $payload);
        $this->assertStringNotContainsString('worker@example.com', $payload);
        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $serializedJob = Crypt::decrypt($data['data']['command']);
        $this->assertStringContainsString('SecretPass123!', $serializedJob);
        $restoredJob = unserialize($serializedJob, ['allowed_classes' => [SendWorkerCredentialsEmailJob::class]]);
        $this->assertSame(config('notifications.queues.normal'), $restoredJob->queue);
        $this->assertCredentialsSent('SecretPass123!', passwordUpdated: true);
    }

    public function test_delivery_waits_for_transaction_commit(): void
    {
        $transactions = new DatabaseTransactionsManager;
        $this->app->instance('db.transactions', $transactions);
        $transactions->begin('testing', 1);

        $this->dispatchCredentials();
        Mail::assertNothingSent();

        $transactions->commit('testing', 1, 0);

        $this->assertCredentialsSent('SecretPass123!', passwordUpdated: true);
    }

    public function test_transaction_rollback_discards_the_email(): void
    {
        $transactions = new DatabaseTransactionsManager;
        $this->app->instance('db.transactions', $transactions);
        $transactions->begin('testing', 1);

        $this->dispatchCredentials();
        Mail::assertNothingSent();

        $transactions->rollback('testing', 0);
        Mail::assertNothingSent();
    }

    private function dispatchCredentials(): void
    {
        SendWorkerCredentialsEmailJob::dispatch(
            name: 'Test Worker',
            email: 'worker@example.com',
            password: 'SecretPass123!',
            passwordUpdated: true,
        )->onConnection('sync');
    }

    private function worker(string $email = 'worker@example.com'): Worker
    {
        $user = new User(['name' => 'Test Worker', 'email' => $email, 'type' => PortalTypeEnum::WORKER]);
        $user->id = 10;
        $worker = new Worker(['user_id' => 10, 'phone' => '01234567890', 'is_active' => true]);
        $worker->id = 42;
        $worker->setRelation('user', $user);
        $worker->setRelation('media', new Collection);
        $user->setRelation('worker', $worker);

        return $worker;
    }

    private function controller(): AdminWorkerController
    {
        return new AdminWorkerController(Mockery::mock(WorkerRepositoryInterface::class));
    }

    private function assertCredentialsSent(string $password, bool $passwordUpdated, string $email = 'worker@example.com'): void
    {
        Mail::assertSentCount(1);
        Mail::assertSent(WorkerCredentialsMail::class, fn (WorkerCredentialsMail $mail): bool => $mail->hasTo($email)
            && $mail->name === 'Test Worker'
            && $mail->email === $email
            && $mail->password === $password
            && $mail->passwordUpdated === $passwordUpdated
        );
    }
}
