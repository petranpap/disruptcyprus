<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryResource;
use App\Models\Industry;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class IndustryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $industries = Cache::rememberForever(
            Industry::CACHE_KEY,
            fn () => Industry::query()->active()->ordered()->with('media')->get(),
        );

        return IndustryResource::collection($industries);
    }
}
