<?php

namespace App\Policies;

use App\Models\PushCampaign;
use App\Models\User;

/**
 * Editors compose and send campaigns; sent campaigns are history and cannot change.
 */
class PushCampaignPolicy extends EditorialPolicy
{
    public function update(User $user, ?PushCampaign $campaign = null): bool
    {
        return parent::update($user) && ($campaign === null || $campaign->isDraft());
    }

    public function delete(User $user, ?PushCampaign $campaign = null): bool
    {
        return parent::update($user) && ($campaign === null || $campaign->isDraft());
    }
}
