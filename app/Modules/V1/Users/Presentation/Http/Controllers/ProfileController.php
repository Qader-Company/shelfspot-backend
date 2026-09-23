<?php

namespace App\Modules\V1\Users\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Application\Profiles\ProfileHandlerFactory;
use App\Modules\V1\Users\Domain\Models\DeviceToken;
use App\Modules\V1\Users\Presentation\Http\Requests\DestroyDeviceTokenRequest;
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
            'message' => __('notifications.device_token_registered'),
            'data' => [
                'id' => $deviceToken->id,
                'device_type' => $deviceToken->device_type,
                'device_name' => $deviceToken->device_name,
                'last_used_at' => $deviceToken->last_used_at,
            ],
        ]);
    }

    public function destroyDeviceToken(DestroyDeviceTokenRequest $request)
    {
        $request->user()
            ->deviceTokens()
            ->where('token', $request->validated('token'))
            ->delete();

        return ApiResponse::deleted(__('notifications.device_token_deleted'));
    }
}
