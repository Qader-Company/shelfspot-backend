<?php

namespace App\Modules\V1\PlatformSettings\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\PlatformSettings\Application\Services\PlatformSettingsService;
use App\Modules\V1\PlatformSettings\Presentation\Http\Requests\UpdatePlatformSettingRequest;
use App\Modules\V1\PlatformSettings\Presentation\Http\Resources\PlatformSettingResource;

class PlatformSettingController extends Controller
{
    public function __construct(private readonly PlatformSettingsService $platformSettingsService) {}

    public function show()
    {
        return ApiResponse::success($this->platformSettingsService->current());
    }

    public function update(UpdatePlatformSettingRequest $request)
    {
        return ApiResponse::updated(new PlatformSettingResource(
            $this->platformSettingsService->update($request->validated())
        ));
    }
}
