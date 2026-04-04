<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\SettingService;
use App\Http\Controllers\Controller;

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

}
