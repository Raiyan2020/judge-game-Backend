<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\SettingService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SettingResource;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(protected SettingService $settingService)
    {
    }

    public function contactSettings()
    {
        $data = $this->settingService->getSettingsByType('contacts');
        return \responder::success($data);
    }

    public function getSettingsPages(Request $request)
    {
        $data = $this->settingService->getSettingsPages($request->type);
        return \responder::success(SettingResource::collection($data));
    }

        public function getSettingsLaws()
        {
            $data = $this->settingService->getSettingsByType('laws');
            return \responder::success($data);
        }

}
