<?php

namespace App\Modules\V1\Payments\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Payments\Domain\Repositories\AdminPaymentRepositoryInterface;
use App\Modules\V1\Payments\Presentation\Http\Requests\AdminPaymentIndexRequest;
use App\Modules\V1\Payments\Presentation\Http\Resources\AdminPaymentResource;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly AdminPaymentRepositoryInterface $payments,
    ) {}

    public function index(AdminPaymentIndexRequest $request)
    {
        $filters = $request->filters();

        return ApiResponse::success([
            'summary' => $this->payments->summary($filters),
            'payments' => AdminPaymentResource::collection(
                $this->payments->paginate($filters, $request->paymentStatus())
            )
                ->response()
                ->getData(true),
        ]);
    }

    public function show(int $id)
    {
        return ApiResponse::success(new AdminPaymentResource(
            $this->payments->find($id)
        ));
    }
}
