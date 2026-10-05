<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Jobs\ExportUserData;
use App\Models\User;
use App\Services\Account\UserDataExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * Queues the export; the user gets a mail + in-app notification with a signed download link.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        ExportUserData::dispatch($user);

        return response()->json(['message' => __('notifications.data_export.queued')], 202);
    }

    /**
     * Reached through a temporary signed URL, so it does not require a session.
     */
    public function download(User $user, string $file): StreamedResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}\.json$/', $file) === 1, 404);

        $path = UserDataExporter::directoryFor($user).'/'.$file;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'disrupt-cyprus-data-export.json');
    }
}
