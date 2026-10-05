<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Author;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Admin, editor and demo reader accounts. Credentials come from config('app.seed_accounts') (SEED_* env variables) (local/demo only).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('app.seed_accounts.password');

        $this->account((string) config('app.seed_accounts.admin'), 'Admin', UserRole::Admin, $password);

        $editor = $this->account((string) config('app.seed_accounts.editor'), 'Elena Vassiliou', UserRole::Editor, $password);
        Author::query()->where('name', 'Elena Vassiliou')->update(['user_id' => $editor->id]);

        $reader = $this->account((string) config('app.seed_accounts.reader'), 'Demo Reader', UserRole::Reader, $password);

        $followed = ['fintech', 'artificial-intelligence', 'funding-venture-capital', 'maritime', 'cleantech'];
        $notify = ['artificial-intelligence', 'funding-venture-capital'];

        $reader->industries()->sync(
            Industry::query()->whereIn('slug', $followed)->get()
                ->mapWithKeys(fn (Industry $industry) => [$industry->id => ['notify' => in_array($industry->slug, $notify, true)]])
                ->all()
        );

        $reader->preferences()->update([
            'digest_news_daily' => true,
            'digest_events_weekly' => true,
        ]);
    }

    private function account(string $email, string $name, UserRole $role, string $password): User
    {
        return User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'role' => $role,
            'email_verified_at' => now(),
            'consent_at' => now(),
            'consent_version' => config('app.consent_version'),
            'onboarded_at' => now(),
        ]);
    }
}
