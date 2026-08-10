<?php

namespace App\Enums;

enum ChannelConnectionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Error = 'error';
    case Disconnected = 'disconnected';
}