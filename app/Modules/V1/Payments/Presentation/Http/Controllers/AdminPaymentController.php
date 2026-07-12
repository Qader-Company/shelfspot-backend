<?php

namespace App\Modules\V1\Payments\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Payments\Presentation\Http\Requests\AdminPaymentIndexRequest;
use App\Modules\V1\Payments\Presentation\Http\Resources\AdminPaymentResource;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminPaymentController extends Controller
{
    public function index(AdminPaymentIndexRequest $request)
    {
        $filters = $request->filters();
        $payments = $this->paymentQuery($filters, $request->paymentStatus())
            ->latest('created_at')
            ->paginate();

        return ApiResponse::success([
            'summary' => $this->summary($filters),
            'payments' => AdminPaymentResource::collection($payments)
                ->response()
                ->getData(true),
        ]);
    }

    public function show(int $id)
    {
        $payment = Task::query()
            ->with('company')
            ->whereKey($id)
            ->whereIn('payment_status', TaskPaymentStatusEnum::values())
            ->first();

        if (! $payment) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return ApiResponse::success(new AdminPaymentResource($payment));
    }

    private function summary(array $filters): array
    {
        $summaryQuery = $this->paymentQuery($filters, null, includeStatusFilter: false);

        $totalIncoming = (clone $summaryQuery)
            ->where('payment_status', TaskPaymentStatusEnum::CHARGED->value)
            ->sum('total_price');

        $totalOutgoing = (clone $summaryQuery)
            ->where('payment_status', TaskPaymentStatusEnum::REFUNDED->value)
            ->sum('total_price');

        return [
            'total_incoming' => (float) $totalIncoming,
            'total_outgoing' => (float) $totalOutgoing,
            'net_balance' => (float) $totalIncoming - (float) $totalOutgoing,
        ];
    }

    private function paymentQuery(
        array $filters,
        ?TaskPaymentStatusEnum $paymentStatus,
        bool $includeStatusFilter = true
    ): Builder {
        return Task::query()
            ->with('company')
            ->whereIn('payment_status', TaskPaymentStatusEnum::values())
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('company', function (Builder $companyQuery) use ($search) {
                    $companyQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['company_id'] ?? null, fn (Builder $query, int $companyId) => $query->where('company_id', $companyId))
            ->when($includeStatusFilter && $paymentStatus, fn (Builder $query) => $query->where('payment_status', $paymentStatus->value))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
