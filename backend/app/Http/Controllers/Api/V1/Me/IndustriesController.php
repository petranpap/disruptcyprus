<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdateIndustriesRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustriesController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->respond($user);
    }

    public function update(UpdateIndustriesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->industries()->sync($request->syncPayload());

        return $this->respond($user);
    }

    private function respond(User $user): JsonResponse
    {
        $rows = $user->industries()->orderBy('sort_order')->get(['industries.id']);

        return response()->json(['data' => [
            'industry_ids' => $rows->pluck('id')->values(),
            'notify_ids' => $rows->filter(fn ($industry) => (bool) $industry->getRelationValue('pivot')->notify)->pluck('id')->values(),
        ]]);
    }
}
