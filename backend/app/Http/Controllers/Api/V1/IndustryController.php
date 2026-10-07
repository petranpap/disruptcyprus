<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryResource;
use App\Models\Industry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IndustryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payload = Cache::rememberForever(
            Industry::cacheKey(app()->getLocale()),
            fn () => IndustryResource::collection(Industry::query()->active()->ordered()->with('media')->get())->resolve($request),
        );

        return response()->json(['data' => $payload]);
    }
}
