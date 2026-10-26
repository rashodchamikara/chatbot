<?php
// UI metadata only. A planned entry does not enable a backend integration.
// Keep type keys aligned with the channel_connections.type values in your backend.
return [
    'website' => ['label' => 'Website chat', 'mark' => 'WB', 'description' => 'Help visitors on your website.', 'route' => 'admin.websites.create'],
    'whatsapp' => ['label' => 'WhatsApp', 'mark' => 'WA', 'description' => 'Talk to customers on WhatsApp.', 'route' => 'admin.channels.whatsapp.create'],
    'messenger' => ['label' => 'Facebook Messenger', 'mark' => 'FB', 'description' => 'Messages from your Facebook Page.', 'route' => null],
    'instagram' => ['label' => 'Instagram', 'mark' => 'IG', 'description' => 'Customer conversations in Instagram Direct.', 'route' => null],
    'telegram' => ['label' => 'Telegram', 'mark' => 'TG', 'description' => 'Conversations through your Telegram bot.', 'route' => null],
    'email' => ['label' => 'Email', 'mark' => 'EM', 'description' => 'Customer email in your shared inbox.', 'route' => null],
];
