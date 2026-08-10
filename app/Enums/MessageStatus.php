<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Accepted = 'accepted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
}