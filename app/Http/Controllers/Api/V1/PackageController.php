<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PackageResource;
use App\Services\PackageService;

class PackageController extends Controller
{
    public function __construct(protected PackageService $packageService) {}

    public function index()
    {
        $packages = $this->packageService->getActivePackages();
        return \responder::success([PackageResource::collection($packages)]);
    }
}