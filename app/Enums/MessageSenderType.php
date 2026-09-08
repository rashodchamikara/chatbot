<?php

namespace App\Enums;

enum MessageSenderType: string
{
    case Contact = 'contact';

    /**
     * Kept for backwards compatibility with any older rows
     * or code paths that used "customer".
     */
    case Customer = 'customer';

    case Ai = 'ai';
    case Agent = 'agent';
    case System = 'system';
}
