<?php

namespace App\Contracts\Omnichannel;

use App\Data\Omnichannel\InboundMessageData;
use App\Data\Omnichannel\OutboundMessageData;
use App\Data\Omnichannel\SendResult;
use App\Models\ChannelConnection;
use Illuminate\Http\Request;

interface ChannelAdapter
{
   
    public function type(): string;

    
    public function parseInbound(
        ChannelConnection $connection,
        Request $request,
    ): ?InboundMessageData;

    
    public function send(
        ChannelConnection $connection,
        OutboundMessageData $message,
    ): SendResult;
}