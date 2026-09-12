<?php

namespace App\Enums;

enum ChannelType: string
{
    case Website = 'website';
    case WhatsApp = 'whatsapp';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Email = 'email';
    case Sms = 'sms';
    case Telegram = 'telegram';
}