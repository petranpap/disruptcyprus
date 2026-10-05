<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class SectionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $sections = Cache::rememberForever(Section::CACHE_KEY, fn () => Section::query()->ordered()->get());

        return SectionResource::collection($sections);
    }
}
