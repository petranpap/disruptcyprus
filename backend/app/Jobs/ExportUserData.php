<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DataExportReady;
use App\Services\Account\UserDataExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportUserData implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function handle(UserDataExporter $exporter): void
    {
        $fileName = $exporter->export($this->user);

        $this->user->notify(new DataExportReady($fileName));
    }
}
