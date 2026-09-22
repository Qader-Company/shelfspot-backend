<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Application\Profiles\ProfileHandlerFactory;
use App\Modules\V1\Users\Domain\Models\DeviceToken;
use App\Modules\V1\Users\Presentation\Http\Requests\StoreDeviceTokenRequest;
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


    public function storeDeviceToken(StoreDeviceTokenRequest $request)
    {
        $validated = $request->validated();
        $deviceToken = DeviceToken::updateOrCreate(
            [
                'token' => $validated['token'],
            ],
            [
                'user_id' => $request->user()->id,
                'device_type' => $validated['device_type'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'data' => $deviceToken,
        ]);
    }

}
