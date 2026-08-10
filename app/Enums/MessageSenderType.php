<?php

namespace App\Enums;

enum MessageSenderType: string
{
    case Customer = 'customer';
    case Ai = 'ai';
    case Agent = 'agent';
    case System = 'system';
}