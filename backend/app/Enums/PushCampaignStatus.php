<?php

namespace App\Enums;

enum PushCampaignStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
