<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payload = Cache::rememberForever(
            Section::cacheKey(app()->getLocale()),
            fn () => SectionResource::collection(Section::query()->ordered()->get())->resolve($request),
        );

        return response()->json(['data' => $payload]);
    }
}
