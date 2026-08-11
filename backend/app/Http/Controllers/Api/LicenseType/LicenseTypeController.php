<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\LicenseType;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\LicenseType;
use Illuminate\Http\JsonResponse;

class LicenseTypeController extends Controller
{
    /**
     * List all supported license types.
     */
    public function index(): JsonResponse
    {
        $licenseTypes = LicenseType::query()
            ->orderBy('name')
            ->get()
            ->map(fn (LicenseType $license): array => [
                'id' => $license->id,
                'code' => $license->code,
                'name' => $license->name,
                'description' => $license->description,
            ]);

        return ApiResponse::success($licenseTypes->all());
    }
}
