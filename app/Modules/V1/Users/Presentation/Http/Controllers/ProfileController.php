<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Application\Profiles\ProfileHandlerFactory;
use App\Modules\V1\Users\Presentation\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileHandlerFactory $profileHandlerFactory) {}

    public function show(Request $request)
    {
        return ApiResponse::success(
            $this->profileHandlerFactory->for($request->user())->profile($request->user())
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        return ApiResponse::updated(
            $this->profileHandlerFactory->for($request->user())->update(
                $request->user(),
                $request->validated(),
            )
        );
    }
}
